<?php
namespace Modules\Seo\Controllers\Frontend;

use Illuminate\Routing\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class RobotsController extends Controller
{
    public function txt()
    {
        $file = public_path('robots.txt');

        if (File::exists($file)) {
            $mtime   = File::lastModified($file);
            $content = (string) File::get($file);
            $content = $this->normalizeText($content);
        } else {
            // Фолбэк: генерируем валидный robots.txt на лету
            $content = $this->defaultRobots();
            $mtime   = time();
        }

        // ETag/If-None-Match
        $etag = '"' . md5($content) . '"';
        $ifNoneMatch = request()->headers->get('If-None-Match');
        if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
            return $this->notModified($mtime, $etag);
        }

        // If-Modified-Since (имеет смысл только для реального файла; но оставим общий кейс)
        $ifMod = request()->headers->get('If-Modified-Since');
        if ($ifMod && strtotime($ifMod) >= $mtime) {
            return $this->notModified($mtime, $etag);
        }

        return $this->respondTxt($content, $mtime, $etag);
    }

    /**
     * Дефолт: разрешаем всё + добавляем Host и Sitemap (и доп. карты, если включены).
     */
    protected function defaultRobots(): string
    {
        $base = rtrim((string) config('app.url'), '/');
        if ($base === '') {
            $base = rtrim(request()->getSchemeAndHttpHost(), '/');
        }

        $host    = parse_url($base, PHP_URL_HOST) ?: request()->getHost();
        $lines   = [
            'User-agent: *',
            'Disallow:',
            '',
            "Host: {$host}",
            "Sitemap: {$base}/sitemap.xml",
        ];

        // Доп. карты по флагам (не обязательно, но удобно)
        if (config('seo.features.news_sitemap')) {
            $lines[] = "Sitemap: {$base}/news-sitemap.xml";
        }
        if (config('seo.features.images_sitemap')) {
            $lines[] = "Sitemap: {$base}/images-sitemap.xml";
        }

        // Пользовательские строки из конфига (seo.robots.extra_lines = ['Disallow: /tmp', ...])
        $extra = config('seo.robots.extra_lines', []);
        foreach ((array) $extra as $x) {
            $x = trim((string) $x);
            if ($x !== '') $lines[] = $x;
        }

        $lines[] = ''; // завершающая пустая строка
        return implode("\n", $lines);
    }

    /* ---------- helpers ---------- */

    protected function normalizeText(string $text): string
    {
        // Нормализуем переносы, убираем BOM/непечатные, гарантируем финальный перевод строки
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/^\xEF\xBB\xBF/u', '', $text) ?? $text; // убрать UTF-8 BOM
        // Уберём хвостовые пробелы по строкам — косметика
        $text = implode("\n", array_map(fn($l) => rtrim($l, " \t\0\x0B"), explode("\n", $text)));
        if ($text === '' || substr($text, -1) !== "\n") {
            $text .= "\n";
        }
        return $text;
    }

    protected function respondTxt(string $content, int $mtime, ?string $etag = null): Response
    {
        $headers = [
            'Content-Type'              => 'text/plain; charset=UTF-8',
            'Last-Modified'             => gmdate('D, d M Y H:i:s', $mtime) . ' GMT',
            'Cache-Control'             => 'public, max-age=3600, must-revalidate',
            'X-Content-Type-Options'    => 'nosniff',
        ];
        if ($etag) $headers['ETag'] = $etag;

        return new Response($content, 200, $headers);
    }

    protected function notModified(int $mtime, ?string $etag = null): Response
    {
        $headers = [
            'Last-Modified'          => gmdate('D, d M Y H:i:s', $mtime) . ' GMT',
            'Cache-Control'          => 'public, max-age=3600, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($etag) $headers['ETag'] = $etag;

        return new Response('', 304, $headers);
    }
}
