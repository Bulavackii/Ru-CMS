<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\System\Models\Module;

class SyncModules extends Command
{
    protected $signature = 'modules:sync';
    protected $description = 'Сканирует папку modules/ и регистрирует модули в БД';

    public function handle(): void
    {
        $modulesPath = base_path('modules');
        $folders = File::directories($modulesPath);

        foreach ($folders as $folder) {
            $moduleName = basename($folder);
            $jsonPath = $folder . '/module.json';

            if (!File::exists($jsonPath)) {
                $this->warn("⚠️  $moduleName: module.json не найден.");
                continue;
            }

            $data = json_decode(File::get($jsonPath), true);

            if (!isset($data['name'], $data['version'])) {
                $this->error("❌ $moduleName: module.json повреждён или неполный.");
                continue;
            }

            Module::updateOrCreate(
                ['name' => $data['name']],
                [
                    'version' => $data['version'],
                    'active' => $data['active'] ?? false,
                    'installed_at' => now(),
                    'priority' => $data['priority'] ?? 0,
                ]
            );

            $this->info("✅ $moduleName синхронизирован.");
        }

        $this->info('🎉 Все модули синхронизированы.');
    }
}
