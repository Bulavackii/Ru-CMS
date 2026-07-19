<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\BackupDatabase;
use App\Jobs\BackupFiles;
use Illuminate\Support\Facades\Log;

/**
 * 💾 ScheduleBackups - Команда для запуска бэкапов по расписанию
 */
class ScheduleBackups extends Command
{
    protected $signature = 'backup:run {type?}';
    protected $description = 'Запустить резервное копирование (database, files, all)';

    public function handle(): void
    {
        $type = $this->argument('type') ?? 'all';

        switch ($type) {
            case 'database':
                $this->backupDatabase();
                break;
            case 'files':
                $this->backupFiles();
                break;
            case 'all':
                $this->backupDatabase();
                $this->backupFiles();
                break;
            default:
                $this->error("Неизвестный тип: {$type}. Используйте: database, files, all");
        }
    }

    private function backupDatabase(): void
    {
        $this->info('Запуск бэкапа базы данных...');
        
        try {
            BackupDatabase::dispatch();
            $this->info('✅ Бэкап базы данных запущен в фоне');
        } catch (\Exception $e) {
            $this->error('❌ Ошибка при запуске бэкапа БД: ' . $e->getMessage());
            Log::error('Backup command failed', ['error' => $e->getMessage()]);
        }
    }

    private function backupFiles(): void
    {
        $this->info('Запуск бэкапа файлов...');
        
        try {
            BackupFiles::dispatch();
            $this->info('✅ Бэкап файлов запущен в фоне');
        } catch (\Exception $e) {
            $this->error('❌ Ошибка при запуске бэкапа файлов: ' . $e->getMessage());
            Log::error('Files backup command failed', ['error' => $e->getMessage()]);
        }
    }
}

