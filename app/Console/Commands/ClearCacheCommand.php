<?php

namespace App\Console\Commands;

use App\Jobs\ClearOldCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearCacheCommand extends Command
{
    protected $signature = 'cache:clear-old
                            {--queue : Запустить в фоновом режиме}
                            {--tags= : Очистить конкретные теги (через запятую)}';

    protected $description = 'Очистить старый кэш и неиспользуемые теги';

    public function handle(): int
    {
        if ($this->option('queue')) {
            $this->info('Запуск очистки кэша в фоновом режиме...');

            ClearOldCache::dispatch();

            $this->info('✅ Задача добавлена в очередь.');
            return 0;
        }

        $this->info('Очистка кэша...');

        // Если указаны конкретные теги
        if ($this->option('tags')) {
            $tags = explode(',', $this->option('tags'));
            $cleared = 0;

            foreach ($tags as $tag) {
                try {
                    Cache::tags([trim($tag)])->flush();
                    $cleared++;
                    $this->info("✅ Тег '{$tag}' очищен");
                } catch (\Throwable $e) {
                    $this->error("❌ Ошибка очистки тега '{$tag}': " . $e->getMessage());
                }
            }

            $this->info("\nОчищено тегов: {$cleared}");
            return 0;
        }

        // Полная очистка
        try {
            Cache::flush();
            $this->info('✅ Полный кэш очищен');
            return 0;
        } catch (\Throwable $e) {
            $this->error('❌ Ошибка: ' . $e->getMessage());
            return 1;
        }
    }
}
