<?php
namespace Modules\Seo\Controllers\Frontend;

use Illuminate\Routing\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Modules\Seo\Services\SitemapBuilder;

class SitemapController extends Controller
{
    public function xml()
    {
        $dir  = rtrim((string)config('seo.sitemaps.output_dir', public_path('sitemaps')), '/');
        $path = $dir . '/sitemap.xml';

        // Если файла нет — пробуем собрать (только базовый sitemap)
        if (!File::exists($path)) {
            try {
                (new SitemapBuilder())->build($dir);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (File::exists($path)) {
            $mtime = File::lastModified($path);
            $xml   = File::get($path);

            // ETag/If-None-Match
            $etag = '"' . md5($xml) . '"';
            $ifNoneMatch = request()->headers->get('If-None-Match');
            if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
                return $this->notModified($mtime, $etag);
            }

            // If-Modified-Since
            $ifMod = request()->headers->get('If-Modified-Since');
            if ($ifMod && strtotime($ifMod) >= $mtime) {
                return $this->notModified($mtime, $etag);
            }

            return $this->respondXml($xml, $mtime, $etag);
        }

        // Фолбэк на пустой urlset (если сборка не удалась)
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
        return $this->respondXml($xml, time());
    }

    public function news()
    {
        if (!config('seo.features.news_sitemap')) {
            return response('', 404);
        }

        $dir  = rtrim((string)config('seo.sitemaps.output_dir', public_path('sitemaps')), '/');
        $path = $dir . '/news-sitemap.xml';

        if (!File::exists($path)) {
            return response('', 404);
        }

        $mtime = File::lastModified($path);
        $xml   = File::get($path);

        $etag = '"' . md5($xml) . '"';
        $ifNoneMatch = request()->headers->get('If-None-Match');
        if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
            return $this->notModified($mtime, $etag);
        }

        $ifMod = request()->headers->get('If-Modified-Since');
        if ($ifMod && strtotime($ifMod) >= $mtime) {
            return $this->notModified($mtime, $etag);
        }

        return $this->respondXml($xml, $mtime, $etag);
    }

    public function images()
    {
        if (!config('seo.features.images_sitemap')) {
            return response('', 404);
        }

        $dir  = rtrim((string)config('seo.sitemaps.output_dir', public_path('sitemaps')), '/');
        $path = $dir . '/images-sitemap.xml';

        if (!File::exists($path)) {
            return response('', 404);
        }

        $mtime = File::lastModified($path);
        $xml   = File::get($path);

        $etag = '"' . md5($xml) . '"';
        $ifNoneMatch = request()->headers->get('If-None-Match');
        if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
            return $this->notModified($mtime, $etag);
        }

        $ifMod = request()->headers->get('If-Modified-Since');
        if ($ifMod && strtotime($ifMod) >= $mtime) {
            return $this->notModified($mtime, $etag);
        }

        return $this->respondXml($xml, $mtime, $etag);
    }

    protected function respondXml(string $xml, int $mtime, ?string $etag = null): Response
    {
        // Нормализуем переносы — на всякий
        $xml = str_replace(["\r\n", "\r"], "\n", $xml);

        $headers = [
            'Content-Type'              => 'application/xml; charset=UTF-8',
            'Last-Modified'             => gmdate('D, d M Y H:i:s', $mtime) . ' GMT',
            'Cache-Control'             => 'public, max-age=3600, must-revalidate',
            'X-Content-Type-Options'    => 'nosniff',
        ];
        if ($etag) $headers['ETag'] = $etag;

        return new Response($xml, 200, $headers);
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
