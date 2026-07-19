<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Регистрация команд бэкапов
Artisan::command('backup:run {type?}', function ($type = 'all') {
    $this->info('Запуск резервного копирования...');
    
    if ($type === 'all' || $type === 'database') {
        \App\Jobs\BackupDatabase::dispatch();
        $this->info('✅ Бэкап базы данных запущен');
    }
    
    if ($type === 'all' || $type === 'files') {
        \App\Jobs\BackupFiles::dispatch();
        $this->info('✅ Бэкап файлов запущен');
    }
})->purpose('Запустить резервное копирование');

// Проверка истечения лицензии
Artisan::command('license:check', function () {
    $this->info('Проверка истечения лицензии...');
    \App\Jobs\CheckLicenseExpiration::dispatch();
    $this->info('✅ Проверка завершена');
})->purpose('Проверить истечение лицензии и отправить уведомления');