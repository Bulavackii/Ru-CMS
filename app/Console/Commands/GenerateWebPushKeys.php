<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * 🔑 Команда для генерации VAPID ключей для Web Push
 */
class GenerateWebPushKeys extends Command
{
    protected $signature = 'webpush:generate-keys';
    protected $description = 'Генерация VAPID ключей для Web Push уведомлений';

    public function handle()
    {
        $this->info('Генерация VAPID ключей для Web Push...');
        $this->newLine();

        // В реальном проекте используйте библиотеку minishlink/web-push
        // Для демонстрации генерируем случайные ключи
        $publicKey = base64_encode(Str::random(32));
        $privateKey = base64_encode(Str::random(32));

        $this->info('Добавьте следующие ключи в ваш .env файл:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY=' . $publicKey);
        $this->line('VAPID_PRIVATE_KEY=' . $privateKey);
        $this->line('VAPID_SUBJECT=' . config('app.url'));
        $this->newLine();
        $this->warn('⚠️  ВНИМАНИЕ: Для продакшена используйте библиотеку minishlink/web-push для генерации правильных ключей!');
        $this->newLine();
        $this->info('Установите библиотеку: composer require minishlink/web-push');
        $this->info('И используйте: vendor/bin/generate-vapid-keys');

        return Command::SUCCESS;
    }
}

