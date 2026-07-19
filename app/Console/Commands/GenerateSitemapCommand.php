<?php

namespace App\Console\Commands;

use App\Jobs\GenerateSitemap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate
                            {--queue : Запустить в фоновом режиме}
                            {--force : Игнорировать блокировку}';

    protected $description = 'Сгенерировать sitemap.xml';

    public function handle(): int
    {
        // Проверка блокировки
        $lockKey = 'sitemap_generation_lock';
        $locked = Cache::get($lockKey);

        if ($locked && !$this->option('force')) {
            $this->warn('Sitemap уже генерируется или недавно был сгенерирован.');
            $this->info('Используйте --force для принудительного запуска.');
            return 1;
        }

        // Устанавливаем блокировку на 10 минут
        if (!$this->option('force')) {
            Cache::put($lockKey, true, 600);
        }

        if ($this->option('queue')) {
            $this->info('Запуск генерации sitemap в фоновом режиме...');

            GenerateSitemap::dispatch();

            $this->info('✅ Задача добавлена в очередь.');
            $this->info('Проверьте логи: php artisan queue:work');

            return 0;
        }

        $this->info('Генерация sitemap...');

        try {
            $job = new GenerateSitemap();
            $job->handle();

            $this->info('✅ Sitemap успешно сгенерирован!');
            $this->info('Файл: ' . public_path('sitemap.xml'));

            return 0;
        } catch (\Throwable $e) {
            $this->error('❌ Ошибка генерации: ' . $e->getMessage());

            if ($this->option('force')) {
                Cache::forget($lockKey);
            }

            return 1;
        }
    }
}
