<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Modules\News\Models\News;
use Modules\Menu\Models\Page;

class GenerateSitemap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 минут
    public $tries = 3; // 3 попытки

    public function handle(): void
    {
        $startTime = microtime(true);
        Log::info('Sitemap generation started');

        try {
            $xml = $this->generateXml();
            $path = public_path('sitemap.xml');

            File::put($path, $xml);

            $generationTime = round(microtime(true) - $startTime, 3);
            Log::info('Sitemap generated successfully', [
                'time' => $generationTime . 's',
                'size' => File::size($path) . ' bytes',
            ]);

            // Уведомление об успехе
            if (app()->environment('production')) {
                $this->notifySuccess($generationTime);
            }

        } catch (\Throwable $e) {
            Log::error('Sitemap generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Уведомление об ошибке
            if (app()->environment('production')) {
                $this->notifyError($e);
            }

            throw $e;
        }
    }

    private function generateXml(): string
    {
        $urls = [];

        // Главная
        $urls[] = $this->urlEntry(url('/'), now(), 'daily', '1.0');

        // Новости
        News::where('published', true)
            ->select(['slug', 'updated_at'])
            ->chunk(100, function ($news) use (&$urls) {
                foreach ($news as $item) {
                    $urls[] = $this->urlEntry(
                        route('news.show', $item->slug, false),
                        $item->updated_at,
                        'weekly',
                        '0.8'
                    );
                }
            });

        // Статические страницы
        $pages = ['about', 'faq', 'contacts', 'privacy', 'terms'];
        foreach ($pages as $page) {
            $urls[] = $this->urlEntry(
                route("pages.{$page}", false),
                now(),
                'monthly',
                '0.6'
            );
        }

        // Страницы из модуля Menu
        Page::where('published', true)
            ->select(['slug', 'updated_at'])
            ->chunk(50, function ($pages) use (&$urls) {
                foreach ($pages as $page) {
                    $urls[] = $this->urlEntry(
                        route('pages.show', $page->slug, false),
                        $page->updated_at,
                        'weekly',
                        '0.7'
                    );
                }
            });

        return $this->wrapXml($urls);
    }

    private function urlEntry(string $url, $date, string $frequency, string $priority): string
    {
        $dateStr = $date instanceof \DateTime ? $date->format('Y-m-d') : now()->format('Y-m-d');

        return <<<XML
    <url>
        <loc>{$url}</loc>
        <lastmod>{$dateStr}</lastmod>
        <changefreq>{$frequency}</changefreq>
        <priority>{$priority}</priority>
    </url>
XML;
    }

    private function wrapXml(array $urls): string
    {
        $urlList = implode("\n", $urls);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$urlList}
</urlset>
XML;
    }

    private function notifySuccess(float $time): void
    {
        $message = "✅ Sitemap сгенерирован\n";
        $message .= "⏱️ Время: {$time}s\n";
        $message .= "📊 URL: " . url('/sitemap.xml');

        $this->sendTelegram($message);
    }

    private function notifyError(\Throwable $e): void
    {
        $message = "❌ Ошибка генерации sitemap\n";
        $message .= "❗️ " . $e->getMessage() . "\n";
        $message .= "📁 " . $e->getFile() . ":" . $e->getLine();

        $this->sendTelegram($message);
    }

    private function sendTelegram(string $message): void
    {
        try {
            $token = config('services.telegram.token');
            $chatId = config('services.telegram.chat_id');

            if ($token && $chatId) {
                file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query([
                    'chat_id' => $chatId,
                    'text' => $message,
                ]));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send Telegram notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('Sitemap generation failed permanently', [
            'error' => $exception->getMessage(),
        ]);

        // Можно добавить дополнительную логику при провале
    }
}
