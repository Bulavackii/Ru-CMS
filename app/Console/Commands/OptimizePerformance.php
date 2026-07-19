<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class OptimizePerformance extends Command
{
    protected $signature = 'cms:optimize';
    protected $description = 'Оптимизация производительности CMS';

    public function handle()
    {
        $this->info('🚀 Начало оптимизации...');

        // Очистка кеша
        $this->info('🧹 Очистка кеша...');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Оптимизация автозагрузчика
        $this->info('📦 Оптимизация автозагрузчика...');
        Artisan::call('optimize:clear');
        Artisan::call('optimize');

        // Очистка старых данных
        $this->info('🗑️ Очистка старых данных...');
        $this->cleanupOldData();

        // Оптимизация БД
        $this->info('💾 Оптимизация базы данных...');
        $this->optimizeDatabase();

        $this->info('✅ Оптимизация завершена!');
    }

    private function cleanupOldData()
    {
        // Очистка старых черновиков (старше 30 дней)
        \App\Models\ContentDraft::cleanupOldDrafts(30);

        // Очистка старых логов (если есть)
        // DB::table('logs')->where('created_at', '<', now()->subDays(90))->delete();
    }

    private function optimizeDatabase()
    {
        // Оптимизация таблиц (для MySQL)
        if (DB::getDriverName() === 'mysql') {
            $tables = DB::select('SHOW TABLES');
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                DB::statement("OPTIMIZE TABLE `{$tableName}`");
            }
        }
    }
}

