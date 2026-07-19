<?php

namespace Modules\Seo\Services;

use Modules\Seo\Models\SeoPage;
use Illuminate\Support\Facades\File;

class SitemapBuilder
{
    /**
     * Собирает sitemap.
     */
    public function build(string $outputDir = null): array
    {
        $outputDir = rtrim((string)($outputDir ?: config('seo.sitemaps.output_dir', public_path('sitemaps'))), '/');

        if (!is_dir($outputDir)) {
            File::ensureDirectoryExists($outputDir, 0775, true);
        }

        // ---- Базовый абсолютный хост ----
        $base = trim((string) config('seo.base_url', ''));
        if ($base !== '') {
            $base = rtrim($base, '/');
        } else {
            $base = rtrim((string) config('app.url'), '/');
            if ($base === '') {
                $reqBase = request()->getSchemeAndHttpHost();
                if (!empty($reqBase)) {
                    $base = rtrim($reqBase, '/');
                }
            }
            if ($base === '') {
                $base = 'https://localhost';
            }
        }

        $forceHost = (bool) config('seo.force_current_host', false);
        $maxPerFile = (int) config('seo.sitemaps.max_urls', 50000);

        // Берём только индексируемые и не удалённые
        $rows = SeoPage::query()
            ->select(['slug', 'canonical', 'updated_at'])
            ->where('robots_index', true)
            ->orderByDesc('updated_at')
            ->get();

        // Нормализация ссылок и дедуп
        $seen  = [];
        $items = [];
        foreach ($rows as $p) {
            $loc = $this->locForPage($p->slug, $p->canonical, $base, $forceHost);
            if (!$loc) continue;
            if (isset($seen[$loc])) continue;
            $seen[$loc] = true;

            $items[] = [
                'loc'     => $loc,
                'lastmod' => $p->updated_at ? $p->updated_at->toIso8601String() : gmdate('c'),
            ];
        }

        $total       = count($items);
        $sitemapPath = $outputDir . '/sitemap.xml';

        if ($total <= $maxPerFile) {
            $xml = $this->renderUrlset($items);
            $this->atomicWrite($sitemapPath, $xml);
            return ['sitemap' => $sitemapPath, 'count' => $total, 'indexed' => false];
        }

        // Разбиваем на части и создаём индекс
        $parts  = [];
        $chunks = array_chunk($items, $maxPerFile);
        foreach ($chunks as $i => $chunk) {
            $partPath = sprintf('%s/sitemap-%d.xml', $outputDir, $i + 1);
            $this->atomicWrite($partPath, $this->renderUrlset($chunk));
            $parts[] = $partPath;
        }

        $indexXml = $this->renderIndex($parts, $base, $outputDir);
        $this->atomicWrite($sitemapPath, $indexXml);

        return ['sitemap' => $sitemapPath, 'count' => $total, 'parts' => $parts, 'indexed' => true];
    }

    /* ---------- helpers ---------- */

    /**
     * Вычисляет абсолютный URL страницы.
     * Приоритет: canonical (абсолютный/относительный) → slug (абсолютный/относительный).
     * Если включён force_current_host, любой «чужой» хост переписывается на текущий.
     */
    protected function locForPage(?string $slug, ?string $canonical, string $base, bool $forceHost = false): ?string
    {
        $slug      = trim((string) $slug);
        $canonical = $canonical !== null ? trim((string) $canonical) : null;

        // 1) canonical абсолютный
        if ($canonical && preg_match('~^https?://~i', $canonical)) {
            return $forceHost ? $this->rewriteHostToBase($canonical, $base) : $canonical;
        }
        // 2) canonical относительный
        if ($canonical && !preg_match('~^https?://~i', $canonical)) {
            return $this->absUrl($canonical, $base);
        }

        // 3) slug абсолютный
        if ($slug && preg_match('~^https?://~i', $slug)) {
            return $forceHost ? $this->rewriteHostToBase($slug, $base) : $slug;
        }
        // 4) slug относительный
        if ($slug) {
            return $this->absUrl($slug, $base);
        }

        return null;
    }

/**
     * Переписать хост абсолютного URL на базовый домен, сохранив путь и query.
     */
    protected function rewriteHostToBase(string $absUrl, string $base): string
    {
        $p = parse_url($absUrl);
        $path  = $p['path']  ?? '/';
        $query = isset($p['query']) && $p['query'] !== '' ? ('?' . $p['query']) : '';
        return rtrim($base, '/') . $this->normalizePath($path) . $query;
    }

    protected function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        if (strlen($path) > 1) $path = rtrim($path, '/');
        return $path;
    }

    /**
     * Абсолютный URL из относительного.
     */
    protected function absUrl(string $path, string $base): string
    {
        return rtrim($base, '/') . $this->normalizePath($path);
    }

    protected function renderUrlset(array $urls): string
    {
        $items = array_map(function ($u) {
            $loc    = $this->esc($u['loc']);
            $last   = $this->esc($u['lastmod']);
            return "<url><loc>{$loc}</loc><lastmod>{$last}</lastmod></url>";
        }, $urls);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . implode('', $items)
            . '</urlset>';
    }

    /**
     * Генерирует sitemap-index.
     */
    protected function renderIndex(array $partPaths, string $base, string $outputDir): string
    {
        $items = [];
        foreach ($partPaths as $p) {
            $mtime   = @filemtime($p) ?: time();
            $loc     = $this->publicUriFor($p, $base, $outputDir);
            $locEsc  = $this->esc($loc);
            $lastEsc = $this->esc(gmdate('c', $mtime));
            $items[] = "<sitemap><loc>{$locEsc}</loc><lastmod>{$lastEsc}</lastmod></sitemap>";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . implode('', $items)
            . '</sitemapindex>';
    }

    /**
     * Путь файла → публичный URL.
     */
    protected function publicUriFor(string $filePath, string $base, string $outputDir): string
    {
        $publicRoot = str_replace('\\', '/', realpath(public_path()) ?: public_path());
        $absFile    = str_replace('\\', '/', realpath($filePath) ?: $filePath);
        $absDir     = str_replace('\\', '/', realpath($outputDir) ?: $outputDir);

        if (str_starts_with($absDir, $publicRoot)) {
            $relativeDir = substr($absDir, strlen($publicRoot));
            if ($relativeDir === false) $relativeDir = '';
            $relativeDir = '/' . ltrim($relativeDir, '/');
            return rtrim($base, '/') . rtrim($relativeDir, '/') . '/' . basename($filePath);
        }

        return rtrim($base, '/') . '/sitemaps/' . basename($filePath);
    }

    protected function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    protected function atomicWrite(string $path, string $content): void
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $tmp = $path . '.tmp';
        File::put($tmp, $content, true);
        @chmod($tmp, 0644);
        rename($tmp, $path);
    }
}
