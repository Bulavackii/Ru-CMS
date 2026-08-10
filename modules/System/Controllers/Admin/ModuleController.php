<?php

namespace Modules\System\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\System\Models\Module;
use Modules\System\Models\ModuleSignature;
use Modules\System\Services\ModuleSecurityService;
use Modules\System\Services\ProtectedModulesService;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class ModuleController extends Controller
{
    /**
     * 📦 Отображение списка всех модулей
     */
    public function index(Request $request): View
    {
        $query = Module::query();

        // Поиск по названию.
        // ⚠️ На PostgreSQL оператор LIKE регистрозависимый: запрос «меню» не
        // находил модуль «Меню», и поиск выглядел сломанным. Оператор
        // выбирается по драйверу — тот же приём, что в подборщике медиатеки.
        if ($request->filled('search')) {
            $search = $request->search;
            $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $query->where(function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                  ->orWhere('title', $like, "%{$search}%");
            });
        }

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('active', $request->status === 'active');
        }

        // Фильтр по подписи
        if ($request->filled('signed')) {
            $signedNames = \Modules\System\Models\ModuleSignature::pluck('module_name')->toArray();
            if ($request->signed === 'yes') {
                $query->whereIn('name', $signedNames);
            } else {
                $query->whereNotIn('name', $signedNames);
            }
        }

        // Фильтр по защищенным модулям
        if ($request->filled('protected')) {
            $protectedNames = ProtectedModulesService::getProtectedModules();
            if ($request->protected === 'yes') {
                $query->whereIn('name', $protectedNames);
            } else {
                $query->whereNotIn('name', $protectedNames);
            }
        }

        // Сводка считается по ВСЕЙ выборке, а не по странице: иначе на
        // второй странице «Всего» и «Активных» показывали бы её содержимое.
        $totalCount = (clone $query)->count();
        $activeCount = (clone $query)->where('active', true)->count();

        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;

        $modules = $query->orderBy('priority')->paginate($perPage)->withQueryString();

        $modules->getCollection()->transform(function ($module) {
            $module->is_installed = is_dir(base_path("modules/{$module->name}"));
            $module->is_signed = \Modules\System\Models\ModuleSignature::where('module_name', $module->name)->exists();
            $module->is_protected = ProtectedModulesService::isProtected($module->name);
            $module->can_delete = ProtectedModulesService::canDelete($module->name);
            $module->can_disable = ProtectedModulesService::canDisable($module->name);
            return $module;
        });

        // Эти два счёта требуют проверки файлов и списка защищённых, поэтому
        // считаются по именам всей выборки, а не запросом.
        $allNames = (clone $query)->pluck('name');
        $missingCount = $allNames->filter(fn ($name) => ! is_dir(base_path("modules/{$name}")))->count();
        $protectedCount = $allNames->filter(fn ($name) => ProtectedModulesService::isProtected($name))->count();

        return view('admin.modules', compact(
            'modules', 'totalCount', 'activeCount', 'missingCount', 'protectedCount'
        ));
    }

    /**
     * 🔁 Переключение активности модуля (вкл/выкл)
     */
    public function toggle($id)
    {
        try {
            $module = Module::findOrFail($id);

            // Проверка существования модуля в файловой системе
            $modulePath = base_path("modules/{$module->name}");
            if (!is_dir($modulePath)) {
                return back()->withErrors(['module' => __('admin.errors.module_missing_files', ['name' => $module->title])]);
            }

            // 🛡️ Защита ключевых модулей от отключения
            if (ProtectedModulesService::isProtected($module->name)) {
                // Если модуль активен - не даем его отключить
                if ($module->active) {
                    return back()->withErrors(['module' => __('admin.errors.module_system_keep_active', ['name' => $module->title])]);
                }
                // Если модуль неактивен - не даем его активировать (должен быть всегда активен)
                return back()->withErrors(['module' => __('admin.errors.module_system_always_active', ['name' => $module->title])]);
            }

            // Проверка подписи перед активацией.
            //
            // ⚠️ Раньше здесь стояло просто !verifyModule(), а verifyModule()
            // возвращает false и когда подписи НЕТ вовсе. Подписей в системе не
            // создаётся (таблица module_signatures пуста), поэтому включить
            // обратно любой отключённый модуль было НЕВОЗМОЖНО: «выключить» была
            // односторонняя дверь, вернуть модуль можно было только правкой БД.
            //
            // Подпись должна защищать от ПОДМЕНЫ подписанного модуля, а не
            // запрещать неподписанные: если запись о подписи есть — она обязана
            // сойтись, если её нет — модуль считается локальным и включается.
            if (!$module->active && ModuleSignature::where('module_name', $module->name)->exists()) {
                if (!ModuleSecurityService::verifyModule($modulePath, $module->name)) {
                    return back()->withErrors(['module' => __('admin.errors.module_tampered', ['name' => $module->title])]);
                }
            }

            $module->active = !$module->active;
            $module->save();

            $status = $module->active ? 'module_enabled' : 'module_disabled';
            return redirect()->route('admin.modules.index')
                ->with('success', __('admin.flash.' . $status, ['name' => $module->title]));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Module toggle: Module not found", ['id' => $id]);
            return back()->withErrors(['module' => __('admin.errors.module_not_found')]);
        } catch (\Exception $e) {
            Log::error("Module toggle error", ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withErrors(['module' => __('admin.errors.module_toggle_error', ['message' => $e->getMessage()])]);
        }
    }

    /**
     * 📥 Установка нового модуля из ZIP-архива
     */
    public function install(Request $request)
    {
        $request->validate([
            'module' => [
                'required',
                'file',
                'mimes:zip',
                'max:50000', // 50MB
            ],
            'signature' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $file = $request->file('module');
            $filename = $file->getClientOriginalName();
            $moduleName = pathinfo($filename, PATHINFO_FILENAME);

            // Проверка на существующий модуль
            if (Module::where('name', $moduleName)->exists()) {
                return back()->withErrors(['module' => __('admin.errors.module_name_exists', ['name' => $moduleName])]);
            }

            // Временное хранилище
            $zipPath = storage_path("app/temp/{$filename}");
            if (!is_dir(dirname($zipPath))) {
                File::makeDirectory(dirname($zipPath), 0755, true);
            }
            $file->move(dirname($zipPath), $filename);

            // Распаковка
            $extractPath = base_path("modules/{$moduleName}");
            if (!is_dir($extractPath)) {
                File::makeDirectory($extractPath, 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath) === true) {
                // Проверка количества файлов в архиве (защита от DoS)
                $maxFiles = 10000;
                if ($zip->numFiles > $maxFiles) {
                    $zip->close();
                    File::deleteDirectory($extractPath);
                    File::delete($zipPath);
                    DB::rollBack();
                    return back()->withErrors(['module' => __('admin.errors.archive_too_many_files', ['max' => $maxFiles])]);
                }

                // Безопасная распаковка
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);

                    // Защита от Zip Slip.
                    //
                    // ⚠️ Раньше здесь вызывался realpath() для ЕЩЁ НЕ РАСПАКОВАННОГО
                    // файла. realpath() возвращает false для несуществующего пути,
                    // strpos(false, …) никогда не даёт 0 — и условие срабатывало на
                    // КАЖДОЙ записи любого архива. Установка модулей была сломана
                    // полностью: любой корректный ZIP отвергался с «опасным путём».
                    //
                    // Проверяем путь строкой, до распаковки: запрещаем выход вверх
                    // (..), абсолютные пути и Windows-диски.
                    $normalized = str_replace('\\', '/', (string) $entry);
                    $isAbsolute = str_starts_with($normalized, '/')
                        || preg_match('~^[a-zA-Z]:/~', $normalized) === 1;
                    $escapesRoot = in_array('..', explode('/', $normalized), true);

                    if ($isAbsolute || $escapesRoot) {
                        $zip->close();
                        File::deleteDirectory($extractPath);
                        File::delete($zipPath);
                        DB::rollBack();
                        return back()->withErrors(['module' => __('admin.errors.archive_bad_path', ['path' => $entry])]);
                    }

                    // Проверка на PHP файлы в корне (может быть вредоносным)
                    if (pathinfo($entry, PATHINFO_EXTENSION) === 'php' && dirname($entry) === '.') {
                        $content = $zip->getFromIndex($i);
                        if (preg_match('/\beval\s*\(|\bexec\s*\(|\bsystem\s*\(/i', $content)) {
                            $zip->close();
                            File::deleteDirectory($extractPath);
                            File::delete($zipPath);
                            DB::rollBack();
                            return back()->withErrors(['module' => __('admin.errors.archive_malicious')]);
                        }
                    }
                }

                $zip->extractTo($extractPath);
                $zip->close();
            } else {
                File::deleteDirectory($extractPath);
                File::delete($zipPath);
                DB::rollBack();
                return back()->withErrors(['module' => __('admin.errors.archive_unpack_error')]);
            }

            // Проверка module.json
            $configPath = "{$extractPath}/module.json";
            if (!File::exists($configPath)) {
                File::deleteDirectory($extractPath);
                File::delete($zipPath);
                DB::rollBack();
                return back()->withErrors(['module' => __('admin.errors.module_json_missing')]);
            }

            $data = json_decode(File::get($configPath), true);
            if (!$data || !isset($data['name'], $data['version'])) {
                File::deleteDirectory($extractPath);
                File::delete($zipPath);
                DB::rollBack();
                return back()->withErrors(['module' => __('admin.errors.module_json_invalid')]);
            }

            // Проверка безопасности
            $warnings = ModuleSecurityService::scanForMaliciousCode($extractPath);
            if (!empty($warnings)) {
                Log::warning("ModuleSecurity: Malicious code detected in {$moduleName}", $warnings);
                // Можно добавить флаг опасного модуля
            }

            // Проверка цифровой подписи (если предоставлена)
            $signatureValid = false;
            if ($request->has('signature')) {
                $signatureValid = ModuleSecurityService::verifyModule($extractPath, $data['name']);
                if (!$signatureValid) {
                    Log::warning("ModuleSecurity: Invalid signature provided for {$moduleName}");
                }
            }

            // Создание/обновление записи в БД
            $module = Module::updateOrCreate(
                ['name' => $data['name']],
                [
                    'title'    => $data['title'] ?? $data['name'],
                    'version'  => $data['version'],
                    'priority' => $data['priority'] ?? Module::max('priority') + 1,
                    'active'   => $data['active'] ?? false,
                ]
            );

            // Сохранение подписи если она была валидна
            if ($signatureValid && $request->has('signature')) {
                $keys = ModuleSecurityService::generateKeys();
                $signature = ModuleSecurityService::signModule($extractPath, $keys['private']);
                ModuleSecurityService::storeSignature($data['name'], $signature, $keys['public']);
            }

            // Очистка временного файла
            File::delete($zipPath);

            DB::commit();

            $message = "Модуль «{$module->title}» успешно установлен!";
            if (!empty($warnings)) {
                $message .= " ⚠️ Обнаружены подозрительные операции, проверьте код.";
            }

            return redirect()->route('admin.modules.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Module install error", ['error' => $e->getMessage()]);
            return back()->withErrors(['module' => __('admin.errors.install_error', ['message' => $e->getMessage()])]);
        }
    }

    /**
     * 🗑 Удаление модуля
     */
    public function destroy($id)
    {
        try {
            $module = Module::findOrFail($id);

            // 🛡️ Защита ключевых модулей от удаления
            if (ProtectedModulesService::isProtected($module->name)) {
                return back()->withErrors(['module' => __('admin.errors.module_system_no_delete', ['name' => $module->title])]);
            }

            $moduleDir = base_path("modules/{$module->name}");

            // Создание резервной копии перед удалением
            if (File::exists($moduleDir)) {
                $backupPath = $this->createBackup($module);
                if ($backupPath) {
                    Log::info("Module backup created before deletion", [
                        'module' => $module->name,
                        'backup' => $backupPath,
                    ]);
                }
            }

            DB::beginTransaction();

            try {
                // Удаление файлов
                if (File::exists($moduleDir)) {
                    File::deleteDirectory($moduleDir);
                }

                // Удаление подписи
                \Modules\System\Models\ModuleSignature::where('module_name', $module->name)->delete();

                // Удаление записи из БД
                $module->delete();

                DB::commit();

                $message = "Модуль «{$module->title}» был удалён.";
                if (isset($backupPath)) {
                    $message .= " Резервная копия сохранена.";
                }

                return redirect()->route('admin.modules.index')->with('success', $message);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Module delete error", ['error' => $e->getMessage(), 'module' => $module->name]);
                return back()->withErrors(['module' => __('admin.errors.delete_error', ['message' => $e->getMessage()])]);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Module delete: Module not found", ['id' => $id]);
            return back()->withErrors(['module' => __('admin.errors.module_not_found')]);
        } catch (\Exception $e) {
            Log::error("Module delete error", ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withErrors(['module' => __('admin.errors.delete_error', ['message' => $e->getMessage()])]);
        }
    }

    /**
     * 💾 Создание резервной копии модуля
     */
    protected function createBackup(Module $module): ?string
    {
        try {
            $moduleDir = base_path("modules/{$module->name}");
            if (!File::exists($moduleDir)) {
                return null;
            }

            $backupDir = storage_path("app/backups/modules");
            if (!is_dir($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $backupPath = "{$backupDir}/{$module->name}_" . date('Y-m-d_His') . '.zip';

            $zip = new ZipArchive;
            if ($zip->open($backupPath, ZipArchive::CREATE) === true) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($moduleDir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $file) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($moduleDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }

                // Добавляем метаданные модуля
                $metadata = [
                    'name' => $module->name,
                    'title' => $module->title,
                    'version' => $module->version,
                    'priority' => $module->priority,
                    'active' => $module->active,
                    'deleted_at' => now()->toIso8601String(),
                ];
                $zip->addFromString('backup_metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

                $zip->close();
                return $backupPath;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Module backup error", ['module' => $module->name, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 📦 Архивация модуля
     */
    public function archive($id)
    {
        $module = Module::findOrFail($id);
        $moduleDir = base_path("modules/{$module->name}");

        if (!File::exists($moduleDir)) {
            return back()->with('error', __('admin.flash.module_files_missing'));
        }

        $archiveDir = base_path('modules/archives');
        if (!File::exists($archiveDir)) {
            File::makeDirectory($archiveDir, 0755, true);
        }

        $zipPath = "{$archiveDir}/{$module->name}.zip";

        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($moduleDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($moduleDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }

            // Добавляем файл подписи если есть
            $signature = \Modules\System\Models\ModuleSignature::where('module_name', $module->name)->first();
            if ($signature) {
                $signatureData = [
                    'signature' => $signature->signature,
                    'public_key' => $signature->public_key,
                    'signed_at' => $signature->signed_at,
                    'hash_algorithm' => $signature->hash_algorithm,
                ];
                $zip->addFromString('signature.json', json_encode($signatureData, JSON_PRETTY_PRINT));
            }

            $zip->close();

            return back()->with('success', __('admin.flash.module_archived', ['name' => $module->title]));
        }

        return back()->with('error', __('admin.flash.archive_failed'));
    }

    /**
     * ⬇️ Скачать архив модуля
     */
    public function downloadArchive($name)
    {
        $archivePath = base_path("modules/archives/{$name}.zip");

        if (!File::exists($archivePath)) {
            abort(404, 'Архив не найден.');
        }

        return response()->download($archivePath, "{$name}.zip");
    }

    /**
     * 🔢 Drag-and-drop сортировка приоритетов
     */
    public function reorder(Request $request)
    {
        // Раньше массив брался как есть: пустой/кривой запрос ронял метод
        // (foreach по null, обращение к $item['id'] у скаляра) — 500 вместо
        // внятного ответа. Теперь состав данных проверяется.
        $data = $request->validate([
            'order'            => 'required|array|min:1',
            'order.*.id'       => 'required|integer|exists:modules,id',
            'order.*.priority' => 'required|integer|min:0|max:9999',
        ]);

        foreach ($data['order'] as $item) {
            Module::where('id', $item['id'])->update(['priority' => $item['priority']]);
        }

        return response()->json(['status' => 'ok', 'updated' => count($data['order'])]);
    }

    /**
     * 🔑 Генерация ключей для модуля (для разработчиков)
     */
    public function generateKeys($id)
    {
        $module = Module::findOrFail($id);

        $keys = ModuleSecurityService::generateKeys();

        // Сохраняем публичный ключ в БД
        $signature = ModuleSecurityService::signModule(
            base_path("modules/{$module->name}"),
            $keys['private']
        );

        ModuleSecurityService::storeSignature($module->name, $signature, $keys['public']);

        return back()->with('success', __('admin.flash.module_signed', ['name' => $module->title]));
    }

    /**
     * 🛡️ Проверка безопасности модуля
     */
    public function securityCheck($id)
    {
        $module = Module::findOrFail($id);
        $moduleDir = base_path("modules/{$module->name}");

        if (!File::exists($moduleDir)) {
            return back()->with('error', __('admin.flash.module_not_found'));
        }

        $warnings = ModuleSecurityService::scanForMaliciousCode($moduleDir);
        $isSigned = ModuleSecurityService::verifyModule($moduleDir, $module->name);

        return back()->with('security_report', [
            'module' => $module->title,
            'signed' => $isSigned,
            'warnings' => $warnings,
            'safe' => empty($warnings) && $isSigned,
        ]);
    }

    /**
     * 🔄 Массовое переключение модулей
     */
    public function bulkToggle(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:modules,id',
            'action' => 'required|in:enable,disable',
        ]);

        $modules = Module::whereIn('id', $request->ids)->get();
        $count = 0;
        $errors = [];

        foreach ($modules as $module) {
            // Пропускаем защищенные модули
            if (ProtectedModulesService::isProtected($module->name)) {
                $errors[] = "Модуль «{$module->title}» защищен и не может быть изменен";
                continue;
            }

            // Проверка существования модуля
            $modulePath = base_path("modules/{$module->name}");
            if (!is_dir($modulePath)) {
                $errors[] = "Модуль «{$module->title}» не найден в файловой системе";
                continue;
            }

            // Проверка подписи при активации
            if ($request->action === 'enable' && !$module->active) {
                if (!ModuleSecurityService::verifyModule($modulePath, $module->name)) {
                    $errors[] = "Модуль «{$module->title}» не имеет валидной подписи";
                    continue;
                }
            }

            $module->active = $request->action === 'enable';
            $module->save();
            $count++;
        }

        $message = "Обработано модулей: {$count}";
        if (!empty($errors)) {
            $message .= ". Ошибок: " . count($errors);
            return redirect()->route('admin.modules.index')
                ->with('success', $message)
                ->with('errors', $errors);
        }

        return redirect()->route('admin.modules.index')->with('success', $message);
    }

    /**
     * 🗑️ Массовое удаление модулей
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:modules,id',
        ]);

        $modules = Module::whereIn('id', $request->ids)->get();
        $count = 0;
        $errors = [];

        foreach ($modules as $module) {
            // Пропускаем защищенные модули
            if (ProtectedModulesService::isProtected($module->name)) {
                $errors[] = "Модуль «{$module->title}» защищен и не может быть удален";
                continue;
            }

            try {
                // Создаем бэкап
                $this->createBackup($module);

                $moduleDir = base_path("modules/{$module->name}");
                if (File::exists($moduleDir)) {
                    File::deleteDirectory($moduleDir);
                }

                \Modules\System\Models\ModuleSignature::where('module_name', $module->name)->delete();
                $module->delete();
                $count++;

            } catch (\Exception $e) {
                $errors[] = "Ошибка удаления «{$module->title}»: {$e->getMessage()}";
                Log::error("Bulk delete error", ['module' => $module->name, 'error' => $e->getMessage()]);
            }
        }

        $message = "Удалено модулей: {$count}";
        if (!empty($errors)) {
            $message .= ". Ошибок: " . count($errors);
            return redirect()->route('admin.modules.index')
                ->with('success', $message)
                ->with('errors', $errors);
        }

        return redirect()->route('admin.modules.index')->with('success', $message);
    }
}
