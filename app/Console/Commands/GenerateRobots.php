<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

class GenerateRobots extends Command
{
    protected $signature = 'robots:generate';
    protected $description = '🤖 Генерирует robots.txt с актуальными маршрутами';

    public function handle(): void
    {
        $this->info('🔧 Генерация robots.txt...');

        // Основные правила для поисковиков
        $lines = [
            "# 🤖 Robots.txt — для поисковых систем",
            "# 📅 Сгенерировано: " . now()->toDateTimeString(),
            "",
            "User-agent: *",
            "Disallow: /admin/",
            "Disallow: /login",
            "Disallow: /register",
            "Disallow: /password",
            "",
            "# ✅ Разрешаем всё остальное",
            "Allow: /",
            "",
        ];

        // Если sitemap.xml существует — добавим
        $sitemapPath = public_path('sitemap.xml');
        if (File::exists($sitemapPath)) {
            $lines[] = '# 🗺️ Sitemap:';
            $lines[] = 'Sitemap: ' . URL::to('/sitemap.xml');
        } else {
            $lines[] = '# ⚠️ Sitemap.xml пока не найден — сгенерируйте его через artisan sitemap:generate';
        }

        // Запись в файл
        File::put(public_path('robots.txt'), implode("\n", $lines));

        $this->info('✅ robots.txt успешно создан в public/robots.txt');
    }
}
