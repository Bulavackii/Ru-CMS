<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Modules\System\Models\Module;

class ModuleService
{
    /**
     * 🔁 Синхронизировать все модули из папки /modules с БД
     * - Добавляет новые
     * - Обновляет существующие
     * - Удаляет отсутствующие на диске
     */
    public function syncModules(): void
    {
        $modulesPath = base_path('modules');
        $directories = File::directories($modulesPath);

        $existingModules = Module::all()->keyBy('name');
        $foundModuleNames = [];

        foreach ($directories as $dir) {
            $name = basename($dir);
            $json = $dir . '/module.json';

            if (!File::exists($json)) {
                // ❌ module.json не найден — не добавляем, но запоминаем
                continue;
            }

            $data = json_decode(File::get($json), true);

            if (!$data || !isset($data['name'], $data['version'])) {
                // ❌ module.json повреждён — пропускаем
                continue;
            }

            $foundModuleNames[] = $data['name'];

            // ✅ Создание или обновление записи
            Module::updateOrCreate(
                ['name' => $data['name']],
                [
                    'version' => $data['version'],
                    'active' => $data['active'] ?? false,
                    'priority' => $data['priority'] ?? 0,
                ]
            );
        }

        // 🧹 Удаляем из БД те модули, у которых нет физически папки или module.json
        $toDelete = $existingModules->keys()->diff($foundModuleNames);

        if ($toDelete->isNotEmpty()) {
            Module::whereIn('name', $toDelete)->delete();
        }
    }
}
