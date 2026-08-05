<?php

namespace Modules\Files\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Files\Models\File;
use Modules\Files\Models\FileCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileController extends Controller
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * 📁 Список файлов
     */
    public function index(Request $request): View
    {
        $query = File::with(['category', 'user']);

        // Фильтры
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            if ($request->type === 'image') {
                $query->where('mime_type', 'like', 'image/%');
            } elseif ($request->type === 'video') {
                $query->where('mime_type', 'like', 'video/%');
            } elseif ($request->type === 'document') {
                $query->whereIn('mime_type', [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ]);
            }
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('original_name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Размер страницы выбирает человек: у кого-то библиотека на три файла,
        // у кого-то на три тысячи. Значение зажато в известные варианты —
        // произвольное число из адреса позволило бы запросить всё разом.
        $perPage = (int) $request->input('per_page', 24);
        $perPage = in_array($perPage, [12, 24, 48, 96], true) ? $perPage : 24;

        $files = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
        $categories = FileCategory::orderBy('name')->get();

        return view('Files::admin.index', compact('files', 'categories'));
    }

    /**
     * 🔎 Список файлов в JSON — для выбора из медиатеки в редакторе.
     *
     * До появления этого метода у модуля не было НИ ОДНОГО эндпоинта, отдающего
     * список: index() возвращает вьюху. Поэтому редактор материалов не мог
     * предложить уже загруженный файл — каждую картинку приходилось загружать
     * заново, и в библиотеке копились дубли одного и того же.
     *
     * Отдаёт ровно то, что нужно вставке (адрес, размеры, подпись), а не всю
     * запись целиком: остальное в редакторе не используется, а отдавать лишнее
     * из административного эндпоинта незачем.
     */
    public function browse(Request $request): JsonResponse
    {
        $query = File::query();

        if ($request->filled('type')) {
            // Тип приходит от кнопки редактора: картинка, видео, звук.
            //
            // Отбор идёт и по MIME, и по расширению: MIME определяется по
            // содержимому и у части файлов приезжает как application/octet-stream
            // (так бывает у .m4a и .mov), а по имени они узнаются надёжно.
            $video = (array) config('files.video_extensions', []);
            $audio = (array) config('files.audio_extensions', []);

            match ($request->input('type')) {
                'image' => $query->where('mime_type', 'like', 'image/%'),
                'video' => $query->where(fn ($q) => $q->where('mime_type', 'like', 'video/%')
                    ->orWhere(fn ($e) => $this->whereExtension($e, $video))),
                'audio' => $query->where(fn ($q) => $q->where('mime_type', 'like', 'audio/%')
                    ->orWhere(fn ($e) => $this->whereExtension($e, $audio))),
                default => $query,
            };
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        if ($request->filled('q')) {
            $search = '%' . $request->input('q') . '%';

            // ilike ради регистронезависимого поиска по-русски: в PostgreSQL
            // like регистр учитывает, и «Логотип» не находился по запросу
            // «логотип». Оператор выбирается по драйверу — боевая база
            // PostgreSQL, но тесты гоняются на SQLite, а он про ilike не знает
            // и уронил бы запрос целиком.
            $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $query->where(function ($inner) use ($search, $like) {
                $inner->where('original_name', $like, $search)
                      ->orWhere('alt_text', $like, $search)
                      ->orWhere('description', $like, $search);
            });
        }

        $files = $query->orderByDesc('created_at')->paginate(30, ['*'], 'page');

        return response()->json([
            'success' => true,
            'files' => collect($files->items())->map(fn (File $file) => [
                'id'        => $file->id,
                'name'      => $file->original_name,
                'url'       => $file->url,
                'mime_type' => $file->mime_type,
                'is_image'  => $file->isImage(),
                // Чем вставлять файл, решает не редактор: правило одно на
                // отбор в списке и на вставку, и живёт оно здесь.
                'kind'      => $this->kindOf($file),
                'ext'       => strtolower(pathinfo((string) $file->original_name, PATHINFO_EXTENSION)),
                'width'     => $file->width,
                'height'    => $file->height,
                'alt_text'  => $file->alt_text,
                'size'      => $file->human_size,
            ])->all(),
            'page'      => $files->currentPage(),
            'last_page' => $files->lastPage(),
            'total'     => $files->total(),
        ]);
    }

    /** Отбор по расширению в имени файла — на любом драйвере одинаково. */
    private function whereExtension($query, array $extensions)
    {
        foreach ($extensions as $extension) {
            $query->orWhere('original_name', 'like', '%.' . $extension);
        }

        return $query;
    }

    /** Картинка, видео, звук или просто файл. */
    private function kindOf(File $file): string
    {
        $mime = (string) $file->mime_type;
        $ext = strtolower(pathinfo((string) $file->original_name, PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/') || in_array($ext, (array) config('files.video_extensions', []), true)) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/') || in_array($ext, (array) config('files.audio_extensions', []), true)) {
            return 'audio';
        }

        return 'file';
    }

    /**
     * Запрещено ли расширение к загрузке.
     *
     * Медиатека принимает файлы любого типа, кроме опасных — список и разбор
     * почему живут в config/files.php. Точка входа одна, чтобы правило не
     * разъехалось между проверкой формы и проверкой самого файла.
     */
    public static function isBlockedExtension(?string $extension): bool
    {
        $extension = strtolower(trim((string) $extension, ". \t\n\r\0\x0B"));

        if ($extension === '') {
            // Файл без расширения веб-сервер отдаст как поток байтов и
            // исполнять не станет — это безопасный случай.
            return false;
        }

        return in_array($extension, array_map('strtolower', (array) config('files.blocked_extensions', [])), true);
    }

    /**
     * 📤 Загрузка файла(ов)
     */
    public function upload(Request $request): JsonResponse
    {
        // Тип файла больше не ограничен белым списком: он мешал класть в
        // библиотеку шрифты, субтитры, чертежи и архивы — всё, ради чего её и
        // открывают. Вместо этого запрещены конкретные опасные расширения
        // (см. config/files.php): файлы лежат в storage/app/public и доступны
        // по прямой ссылке без авторизации, поэтому .php исполнился бы на
        // сервере, а .html и .svg открылись бы как страницы этого домена и
        // получили доступ к сессии администратора.
        $maxSize = max(1024, min(
            (int) config('files.max_size_kb', 262144),
            max_upload_kb((int) config('files.max_size_kb', 262144))
        ));

        $request->validate([
            'file' => "sometimes|file|max:{$maxSize}",
            'files' => 'sometimes|array',
            'files.*' => "file|max:{$maxSize}",
            'category_id' => 'nullable|exists:file_categories,id',
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ], [
            'file.max' => __('admin.files.too_big', ['size' => max_upload_label($maxSize)]),
            'files.*.max' => __('admin.files.too_big', ['size' => max_upload_label($maxSize)]),
        ]);

        try {
            $uploadedFiles = [];
            
            // Поддержка как одного файла, так и массива файлов
            if ($request->hasFile('file')) {
                $uploadedFiles[] = $request->file('file');
            } elseif ($request->hasFile('files')) {
                $uploadedFiles = $request->file('files');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл не был загружен',
                ], 400);
            }

            $createdFiles = [];

            $rejected = [];

            foreach ($uploadedFiles as $uploadedFile) {
                if (!$uploadedFile->isValid()) {
                    continue;
                }

                $originalName = $uploadedFile->getClientOriginalName();
                $mimeType = $uploadedFile->getMimeType();
                $size = $uploadedFile->getSize();

                // Вторая линия защиты: правила валидации можно обойти прямым
                // вызовом метода из другого кода, поэтому проверяем сам файл.
                //
                // Смотрим ВСЕ части имени, а не только последнюю: «отчёт.php.jpg»
                // на части серверов исполняется как PHP, потому что Apache с
                // mod_php разбирает имя по точкам слева направо.
                $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
                $blocked = null;

                foreach (explode('.', $originalName) as $part) {
                    if (self::isBlockedExtension($part)) {
                        $blocked = $part;
                        break;
                    }
                }

                if ($blocked !== null) {
                    $rejected[] = $originalName;
                    Log::warning('Отклонена загрузка файла опасного типа', [
                        'original_name' => $originalName,
                        'extension'     => $blocked,
                        'mime'          => $mimeType,
                        'user_id'       => auth()->id(),
                    ]);
                    continue;
                }

                // Сохранение файла
                $path = $uploadedFile->store('files/' . date('Y/m'), 'public');
                
                $width = null;
                $height = null;

                // Обработка изображений
                if (str_starts_with($mimeType, 'image/')) {
                    try {
                        $image = $this->imageManager->read(Storage::disk('public')->path($path));
                        $width = $image->width();
                        $height = $image->height();

                        // Создание thumbnails
                        $this->createThumbnails($path);
                    } catch (\Exception $e) {
                        // Если не удалось обработать изображение, продолжаем без размеров
                        Log::warning('Failed to process image', ['error' => $e->getMessage()]);
                    }
                }

                $file = File::create([
                    'name' => basename($path),
                    'original_name' => $originalName,
                    'path' => $path,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'width' => $width,
                    'height' => $height,
                    'category_id' => $request->category_id,
                    'user_id' => auth()->id(),
                    'alt_text' => $request->alt_text,
                    'description' => $request->description,
                ]);

                $createdFiles[] = [
                    'id' => $file->id,
                    'url' => $file->url,
                    'name' => $file->original_name,
                    'size' => $file->human_size,
                    // Те же поля, что отдаёт browse(): редактор показывает
                    // превью по виду файла, и без них загруженное видео
                    // рисовалось значком «битое изображение».
                    'kind' => $this->kindOf($file),
                    'ext' => strtolower(pathinfo((string) $file->original_name, PATHINFO_EXTENSION)),
                    'is_image' => $file->isImage(),
                    'alt_text' => $file->alt_text,
                ];
            }

            $message = count($createdFiles) . ' файл(ов) успешно загружено';

            if ($rejected) {
                // Пишем именно «опасный», а не «недопустимый»: разрешено всё,
                // кроме того, что исполняется на сервере или в браузере, и
                // человек должен понимать, что дело не в прихоти списка.
                $message .= '. Отклонены (тип опасен для сайта): ' . implode(', ', $rejected);
            }

            return response()->json([
                'success'  => true,
                'files'    => $createdFiles,
                'rejected' => $rejected,
                'message'  => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('File upload error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🖼️ Обрезка изображения
     */
    public function crop(Request $request, File $file): JsonResponse
    {
        if (!$file->isImage()) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не является изображением',
            ], 400);
        }

        if (!Storage::disk('public')->exists($file->path)) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не найден на диске',
            ], 404);
        }

        $validated = $request->validate([
            'x' => 'required|integer|min:0',
            'y' => 'required|integer|min:0',
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
        ]);

        try {
            $image = $this->imageManager->read(Storage::disk('public')->path($file->path));
            
            $image->crop(
                (int)$validated['width'],
                (int)$validated['height'],
                (int)$validated['x'],
                (int)$validated['y']
            );

            Storage::disk('public')->put($file->path, $image->encode());

            // Обновить размеры
            $file->update([
                'width' => $image->width(),
                'height' => $image->height(),
            ]);

            // Пересоздать thumbnails
            $this->createThumbnails($file->path);

            return response()->json([
                'success' => true,
                'message' => 'Изображение обрезано',
                'url' => $file->url,
                'width' => $file->width,
                'height' => $file->height,
            ]);
        } catch (\Exception $e) {
            Log::error('Image crop error', ['error' => $e->getMessage(), 'file_id' => $file->id]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обрезки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🗑️ Удаление файла
     */
    public function destroy(File $file): JsonResponse
    {
        try {
            // Проверка существования файла
            if (Storage::disk('public')->exists($file->path)) {
                // Удалить файл с диска
                Storage::disk('public')->delete($file->path);
                
                // Удалить thumbnails
                $this->deleteThumbnails($file->path);
            }

            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'Файл удален',
            ]);
        } catch (\Exception $e) {
            Log::error('File deletion error', ['error' => $e->getMessage(), 'file_id' => $file->id]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🗑️ Удаление нескольких файлов разом.
     *
     * Метод с таким же именем есть в App\Http\Controllers\Admin\FileController,
     * и маршрут вёл именно туда — в тот самый дубль ядра, который для медиатеки
     * давно не используется (все остальные действия идут в этот контроллер).
     * Тот дубль сносил запись и файл, но не трогал уменьшенные копии: они
     * оставались на диске навсегда.
     *
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:files,id',
        ]);

        $deleted = 0;
        $failed = [];

        foreach (File::whereIn('id', $validated['ids'])->get() as $file) {
            try {
                if (Storage::disk('public')->exists($file->path)) {
                    Storage::disk('public')->delete($file->path);
                    $this->deleteThumbnails($file->path);
                }

                $file->delete();
                $deleted++;
            } catch (\Throwable $e) {
                // Один сбойный файл не должен обрывать всю пачку: остальные
                // удаляются, а имя проблемного возвращается в ответе.
                $failed[] = $file->original_name;

                Log::error('Не удалось удалить файл пачкой', [
                    'file_id' => $file->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'failed'  => $failed,
            'message' => __('admin.files.bulk_deleted', ['count' => $deleted]),
        ]);
    }

    /**
     * 👁️ Просмотр информации о файле
     */
    public function show(File $file): JsonResponse
    {
        $file->load(['category', 'user']);

        return response()->json([
            'success' => true,
            'file' => [
                'id' => $file->id,
                'name' => $file->original_name,
                // Дублируем под привычным именем: карточка файла во вьюхе читает
                // original_name, и без этого ключа заголовок модалки был пустым.
                'original_name' => $file->original_name,
                'url' => $file->url,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'human_size' => $file->human_size,
                'width' => $file->width,
                'height' => $file->height,
                'is_image' => $file->isImage(),
                'alt_text' => $file->alt_text,
                'description' => $file->description,
                'category' => $file->category ? [
                    'id' => $file->category->id,
                    // У модели Categories поле называется title (не name).
                    'name' => $file->category->title,
                ] : null,
                'user' => $file->user ? [
                    'id' => $file->user->id,
                    'name' => $file->user->name,
                ] : null,
                'created_at' => $file->created_at->format('d.m.Y H:i'),
                'updated_at' => $file->updated_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * ✏️ Обновление метаданных файла
     */
    public function update(Request $request, File $file): JsonResponse
    {
        $validated = $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:file_categories,id',
            'tags' => 'nullable|array',
        ]);

        try {
            $file->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно обновлен',
                'file' => [
                    'id' => $file->id,
                    'alt_text' => $file->alt_text,
                    'description' => $file->description,
                    'category_id' => $file->category_id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📥 Скачивание файла
     */
    public function download(File $file)
    {
        if (!Storage::disk('public')->exists($file->path)) {
            abort(404, 'Файл не найден');
        }

        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    /**
     * 🖼️ Создание thumbnails
     */
    private function createThumbnails(string $path): void
    {
        $sizes = [
            'thumb' => [150, 150],
            'small' => [300, 300],
            'medium' => [800, 800],
        ];

        $image = $this->imageManager->read(Storage::disk('public')->path($path));

        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnail = clone $image;
            $thumbnail->scale($dimensions[0], $dimensions[1]);
            
            $thumbnailPath = $this->getThumbnailPath($path, $sizeName);
            Storage::disk('public')->put($thumbnailPath, $thumbnail->encode());
        }
    }

    /**
     * 🗑️ Удаление thumbnails
     */
    private function deleteThumbnails(string $path): void
    {
        $sizes = ['thumb', 'small', 'medium'];
        
        foreach ($sizes as $size) {
            $thumbnailPath = $this->getThumbnailPath($path, $size);
            Storage::disk('public')->delete($thumbnailPath);
        }
    }

    /**
     * 📍 Получить путь к thumbnail
     */
    private function getThumbnailPath(string $path, string $size): string
    {
        $pathInfo = pathinfo($path);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
    }
}

