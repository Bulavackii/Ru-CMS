<?php

namespace Modules\System\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\System\Models\Module;
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

        // Поиск по названию
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
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

        $modules = $query->orderBy('priority')->get()->map(function ($module) {
            $module->is_installed = is_dir(base_path("modules/{$module->name}"));
            $module->is_signed = \Modules\System\Models\ModuleSignature::where('module_name', $module->name)->exists();
            $module->is_protected = ProtectedModulesService::isProtected($module->name);
            $module->can_delete = ProtectedModulesService::canDelete($module->name);
            $module->can_disable = ProtectedModulesService::canDisable($module->name);
            return $module;
        });

        return view('admin.modules', compact('modules'));
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
                return back()->withErrors(['module' => "⚠️ Модуль «{$module->title}» не найден в файловой системе!"]);
            }

            // 🛡️ Защита ключевых модулей от отключения
            if (ProtectedModulesService::isProtected($module->name)) {
                // Если модуль активен - не даем его отключить
                if ($module->active) {
                    return back()->withErrors(['module' => "⚠️ Модуль «{$module->title}» является системным и должен оставаться активным!"]);
                }
                // Если модуль неактивен - не даем его активировать (должен быть всегда активен)
                return back()->withErrors(['module' => "⚠️ Модуль «{$module->title}» является системным и должен быть всегда активен!"]);
            }

            // Проверка подписи перед активацией (только для незащищенных модулей)
            if (!$module->active && !ModuleSecurityService::verifyModule($modulePath, $module->name)) {
                return back()->withErrors(['module' => "⚠️ Модуль «{$module->title}» не имеет валидной подписи или был изменен!"]);
            }

            $module->active = !$module->active;
            $module->save();

            $status = $module->active ? 'включён' : 'отключён';
            return redirect()->route('admin.modules.index')->with('success', "Модуль «{$module->title}» {$status}.");

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Module toggle: Module not found", ['id' => $id]);
            return back()->withErrors(['module' => 'Модуль не найден']);
        } catch (\Exception $e) {
            Log::error("Module toggle error", ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withErrors(['module' => "Ошибка при переключении модуля: {$e->getMessage()}"]);
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
                return back()->withErrors(['module' => "Модуль с именем '{$moduleName}' уже существует!"]);
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
                    return back()->withErrors(['module' => "⚠️ Архив содержит слишком много файлов (максимум {$maxFiles})"]);
                }

                // Безопасная распаковка
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);

                    // Защита от Zip Slip
                    $entryPath = realpath($extractPath . '/' . $entry);
                    if (strpos($entryPath, realpath($extractPath)) !== 0) {
                        $zip->close();
                        File::deleteDirectory($extractPath);
                        File::delete($zipPath);
                        DB::rollBack();
                        return back()->withErrors(['module' => "⚠️ Обнаружен опасный путь в архиве: {$entry}"]);
                    }

                    // Проверка на PHP файлы в корне (может быть вредоносным)
                    if (pathinfo($entry, PATHINFO_EXTENSION) === 'php' && dirname($entry) === '.') {
                        $content = $zip->getFromIndex($i);
                        if (preg_match('/\beval\s*\(|\bexec\s*\(|\bsystem\s*\(/i', $content)) {
                            $zip->close();
                            File::deleteDirectory($extractPath);
                            File::delete($zipPath);
                            DB::rollBack();
                            return back()->withErrors(['module' => "⚠️ Обнаружен вредоносный код в корневом PHP файле!"]);
                        }
                    }
                }

                $zip->extractTo($extractPath);
                $zip->close();
            } else {
                File::deleteDirectory($extractPath);
                File::delete($zipPath);
                DB::rollBack();
                return back()->withErrors(['module' => 'Ошибка распаковки архива']);
            }

            // Проверка module.json
            $configPath = "{$extractPath}/module.json";
            if (!File::exists($configPath)) {
                File::deleteDirectory($extractPath);
                File::delete($zipPath);
                DB::rollBack();
                return back()->withErrors(['module' => 'Файл module.json не найден']);
            }

            $data = json_decode(File::get($configPath), true);
            if (!$data || !isset($data['name'], $data['version'])) {
                File::deleteDirectory($extractPath);
                File::delete($zipPath);
                DB::rollBack();
                return back()->withErrors(['module' => 'Некорректный формат module.json']);
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
            return back()->withErrors(['module' => "Ошибка установки: {$e->getMessage()}"]);
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
                return back()->withErrors(['module' => "⚠️ Модуль «{$module->title}» является системным и не может быть удален!"]);
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
                return back()->withErrors(['module' => "Ошибка удаления: {$e->getMessage()}"]);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Module delete: Module not found", ['id' => $id]);
            return back()->withErrors(['module' => 'Модуль не найден']);
        } catch (\Exception $e) {
            Log::error("Module delete error", ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withErrors(['module' => "Ошибка удаления: {$e->getMessage()}"]);
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
            return back()->with('error', 'Модуль не найден в файловой системе.');
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

            return back()->with('success', "Архив модуля «{$module->title}» создан в /modules/archives.");
        }

        return back()->with('error', 'Не удалось создать архив.');
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
        foreach ($request->input('order') as $item) {
            Module::where('id', $item['id'])->update(['priority' => $item['priority']]);
        }

        return response()->json(['status' => 'ok']);
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

        return back()->with('success', "Ключи сгенерированы и подпись сохранена для «{$module->title}».");
    }

    /**
     * 🛡️ Проверка безопасности модуля
     */
    public function securityCheck($id)
    {
        $module = Module::findOrFail($id);
        $moduleDir = base_path("modules/{$module->name}");

        if (!File::exists($moduleDir)) {
            return back()->with('error', 'Модуль не найден.');
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
