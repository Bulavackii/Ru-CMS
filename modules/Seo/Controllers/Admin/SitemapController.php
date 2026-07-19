<?php
namespace Modules\Seo\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Modules\Seo\Services\SitemapBuilder;

class SitemapController extends Controller
{
    /**
     * Экран управления Sitemap в админке.
     * Показывает статус файла, тип (index/urlset), счётчики и список частей.
     */
    public function index()
    {
        $dir  = rtrim((string) config('seo.sitemaps.output_dir', public_path('sitemaps')), '/');
        $path = $dir . '/sitemap.xml';

        $exists     = File::exists($path);
        $modifiedAt = $exists ? File::lastModified($path) : null;
        $size       = $exists ? File::size($path) : null;

        $isIndex   = false;
        $urlsCount = null;
        $partsMeta = [];

        if ($exists) {
            // читаем файл (для небольших sitemap это ок)
            $xml = (string) File::get($path);

            // определяем тип
            $isIndex = str_contains($xml, '<sitemapindex');

            if ($isIndex) {
                // соберём мета по частям вида sitemap-*.xml
                $glob = glob($dir . '/sitemap-*.xml') ?: [];
                foreach ($glob as $p) {
                    $partsMeta[] = [
                        'file'  => basename($p),
                        'size'  => File::size($p),
                        'mtime' => File::lastModified($p),
                    ];
                }
            } else {
                // грубый подсчёт <url>, достаточно для дашборда
                $urlsCount = preg_match_all('~<url>~i', $xml, $m);
            }
        }

        return view('seo::admin.sitemaps', compact(
            'exists', 'modifiedAt', 'size', 'isIndex', 'urlsCount', 'partsMeta'
        ));
    }

    /**
     * Пересборка sitemap (через сервис).
     */
    public function rebuild(): RedirectResponse
    {
        try {
            $result  = app(SitemapBuilder::class)->build(); // вернёт [sitemap, count, indexed, parts?]
            $count   = $result['count']   ?? null;
            $indexed = (bool)($result['indexed'] ?? false);
            $parts   = isset($result['parts']) ? count($result['parts']) : 0;

            $details = $count !== null
                ? ($indexed ? "url: {$count}, частей: {$parts}" : "url: {$count}")
                : 'готово';

            return back()->with('status', 'Sitemap пересобран — ' . $details);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('status', 'Ошибка при сборке sitemap: ' . $e->getMessage());
        }
    }
}
