<?php

namespace Modules\Files\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Filesystem;
use Illuminate\Support\Facades\Storage;
use Modules\Files\Models\File;

/**
 * Демо-файлы в медиа-библиотеке после установки.
 *
 * Эталоны лежат в репозитории (modules/Files/Resources/defaults/*.svg) и при
 * сидировании копируются на публичный диск в files/defaults — там же, где живут
 * обычные загрузки (контроллер кладёт их в files/Y/m), поэтому карточка файла,
 * скачивание и вставка ссылки работают одинаково.
 *
 *   php artisan files:seed-default            # добавить недостающее (идемпотентно)
 *   php artisan files:seed-default --reset    # перезаписать файлы и их метаданные
 */
class SeedDefaultFilesCommand extends Command
{
    protected $signature = 'files:seed-default {--reset : Перезаписать демо-файлы и их метаданные}';

    protected $description = 'Наполнить медиа-библиотеку демо-файлами (логотип, обложка, заглушка)';

    /** Куда кладём демо-файлы внутри диска public. */
    private const TARGET_DIR = 'files/defaults';

    /** Канонический набор демо-файлов. */
    public static function definitions(): array
    {
        return [
            [
                'file'        => 'nexum-core-logo.svg',
                'name'        => 'Логотип Nexum Core',
                'width'       => 512,
                'height'      => 512,
                'alt_text'    => 'Логотип Nexum Core',
                'description' => 'Квадратный логотип для шапки сайта и favicon.',
            ],
            [
                'file'        => 'cover-example.svg',
                'name'        => 'Пример обложки',
                'width'       => 1200,
                'height'      => 630,
                'alt_text'    => 'Пример обложки публикации',
                'description' => 'Обложка новости или страницы в формате 1200×630 — подходит и для превью в соцсетях.',
            ],
            [
                'file'        => 'placeholder-16x9.svg',
                'name'        => 'Заглушка 16:9',
                'width'       => 1280,
                'height'      => 720,
                'alt_text'    => 'Заглушка изображения 16:9',
                'description' => 'Временная картинка для материалов без своего изображения.',
            ],
        ];
    }

    /**
     * Копирует демо-файлы на публичный диск и заводит записи в медиа-библиотеке.
     * Идемпотентно: запись ищется по path. $reset=true — перезаписывает файл и метаданные.
     */
    public static function seed(bool $reset = false): void
    {
        $source = base_path('modules/Files/Resources/defaults');

        // Владелец записей — первый администратор (если он уже создан).
        $userId = DB::table('users')->where('is_admin', true)->value('id');

        DB::transaction(function () use ($reset, $source, $userId) {
            foreach (self::definitions() as $def) {
                $relative = self::TARGET_DIR . '/' . $def['file'];
                $sourcePath = $source . DIRECTORY_SEPARATOR . $def['file'];

                if (! Filesystem::exists($sourcePath)) {
                    continue;
                }

                if ($reset || ! Storage::disk('public')->exists($relative)) {
                    Storage::disk('public')->put($relative, Filesystem::get($sourcePath));
                }

                $attributes = [
                    'name'          => $def['name'],
                    'original_name' => $def['file'],
                    'mime_type'     => 'image/svg+xml',
                    'mime'          => 'image/svg+xml',
                    'size'          => Storage::disk('public')->size($relative),
                    'width'         => $def['width'],
                    'height'        => $def['height'],
                    'alt_text'      => $def['alt_text'],
                    'description'   => $def['description'],
                    'user_id'       => $userId,
                ];

                $existing = File::where('path', $relative)->first();

                if (! $existing) {
                    File::create($attributes + ['path' => $relative]);
                    continue;
                }

                if ($reset) {
                    $existing->update($attributes);
                }
            }
        });
    }

    public function handle(): int
    {
        $reset = (bool) $this->option('reset');
        self::seed($reset);

        $names = collect(self::definitions())->pluck('name')->implode(', ');
        $this->info(($reset ? 'Демо-файлы перезаписаны' : 'Демо-файлы проверены/добавлены') . ": {$names}.");

        return self::SUCCESS;
    }
}
