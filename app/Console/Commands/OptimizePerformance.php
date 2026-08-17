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

    /**
     * Обслуживание таблиц.
     *
     * ⚠️ Раньше здесь стояла MySQL-овая перестройка таблиц под условием «если
     * драйвер mysql» — то есть на этом проекте ветка не выполнялась НИКОГДА, и
     * команда молча ничего не оптимизировала.
     *
     * У PostgreSQL за это отвечает VACUUM ANALYZE: пересобирает статистику
     * планировщика и освобождает место от удалённых строк.
     *
     * ⚠️ `VACUUM` нельзя выполнять внутри транзакции, поэтому вызывается
     * через unprepared() — иначе PDO обернёт его и получим «VACUUM cannot
     * run inside a transaction block».
     */
    private function optimizeDatabase()
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->warn('Обслуживание таблиц поддержано только для PostgreSQL — пропускаю.');

            return;
        }

        try {
            DB::connection()->unprepared('VACUUM ANALYZE');
            $this->info('Таблицы обслужены: VACUUM ANALYZE выполнен.');
        } catch (\Throwable $e) {
            // Не роняем всю команду: остальные шаги оптимизации полезны и без
            // этого, а прав на VACUUM может не быть у роли приложения.
            $this->warn('VACUUM ANALYZE не выполнен: ' . $e->getMessage());
        }
    }
}

