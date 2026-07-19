<?php
namespace Modules\Seo\Services;

use Illuminate\Support\Facades\File;

class ImagesSitemapBuilder
{
    /**
     * Строит images-sitemap.
     *
     * Вход:
     *  $pages = [
     *    [
     *      'loc'    => '/news/1' | 'https://site.ru/news/1',
     *      'images' => [
     *        ['url' => '/u/1.jpg', 'caption' => '...', 'title' => '...'],
     *        ...
     *      ],
     *    ],
     *    ...
     *  ]
     *
     * Конфиги (config('seo.images_sitemap.*')):
     *  - max_urls            : макс. URL-узлов (<url>) в одном файле (по умолчанию 50000)
     *  - max_per_page        : макс. картинок на один URL-узел (по умолчанию 1000)
     *  - split_into_index    : разбивать на части и собирать индекс (по умолчанию true)
     *  - index_name          : имя индекс-файла (по умолчанию images-sitemap.xml)
     *
     * @param array<int, array{loc:string,images:array<int,array{url:string,caption?:string,title?:string}>}> $pages
     * @param string|null $outputDir  Папка для вывода; по умолчанию config('seo.sitemaps.output_dir')
     * @return array{
     *    sitemap:string,            // путь к images-sitemap.xml (или к единственному файлу, если индекс не нужен)
     *    count_urls:int,            // кол-во URL-узлов (<url>)
     *    count_images:int,          // общее кол-во image:image
     *    indexed:bool,              // true если построен индекс
     *    parts?:array<int,string>   // пути к частям, если было разбиение
     * }
     */
    public function build(array $pages, string $outputDir = null): array
    {
        $outputDir = rtrim((string)($outputDir ?: config('seo.sitemaps.output_dir', public_path('sitemaps'))), '/');
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0775, true);
        }

        $base = rtrim((string)config('app.url'), '/');
        if ($base === '') {
            $base = rtrim(request()->getSchemeAndHttpHost(), '/');
        }

        $maxUrls       = (int) config('seo.images_sitemap.max_urls', 50000);
        $maxPerPage    = (int) config('seo.images_sitemap.max_per_page', 1000);
        $splitIntoIdx  = (bool) config('seo.images_sitemap.split_into_index', true);
        $indexName     = (string) config('seo.images_sitemap.index_name', 'images-sitemap.xml');

        // Собираем нормализованные сущности: дедуп страниц + дедуп картинок на странице
        $urlNodes  = [];
        $seenPages = [];
        $totalImages = 0;

        foreach ($pages as $p) {
            if (empty($p['loc']) || empty($p['images']) || !is_array($p['images'])) {
                continue;
            }
            $loc = $this->absUrl((string)$p['loc'], $base);
            if (!$this->validUrl($loc)) continue;

            // Если тот же loc встречается много раз — объединим изображения
            if (!isset($seenPages[$loc])) {
                $seenPages[$loc] = count($urlNodes);
                $urlNodes[] = ['loc' => $loc, 'images' => [], 'seen' => []];
            }
            $idx = $seenPages[$loc];

            foreach ((array)$p['images'] as $img) {
                if (count($urlNodes[$idx]['images']) >= $maxPerPage) break;
                $u = $this->absUrl((string)($img['url'] ?? ''), $base);
                if (!$this->validUrl($u)) continue;
                if (isset($urlNodes[$idx]['seen'][$u])) continue;

                $urlNodes[$idx]['seen'][$u] = true;
                $node = ['loc' => $u];
                if (!empty($img['caption'])) $node['caption'] = (string)$img['caption'];
                if (!empty($img['title']))   $node['title']   = (string)$img['title'];
                $urlNodes[$idx]['images'][]  = $node;
                $totalImages++;
            }
        }

        // Удаляем страницы без изображений
        $urlNodes = array_values(array_filter($urlNodes, fn($n) => !empty($n['images'])));
        $totalUrls = count($urlNodes);

        // Ничего валидного — создаём пустой файл (но корректный XML)
        $indexPath = $outputDir . '/' . ltrim($indexName, '/');
        if ($totalUrls === 0) {
            $this->atomicWrite($indexPath, $this->renderUrlset([]));
            return ['sitemap' => $indexPath, 'count_urls' => 0, 'count_images' => 0, 'indexed' => false];
        }

        // Не нужно разбиение — один файл urlset
        if (!$splitIntoIdx || $totalUrls <= $maxUrls) {
            $xml = $this->renderUrlset($urlNodes);
            $this->atomicWrite($indexPath, $xml);
            return [
                'sitemap'       => $indexPath,
                'count_urls'    => $totalUrls,
                'count_images'  => $totalImages,
                'indexed'       => false,
            ];
        }

        // Чанкуем по maxUrls и строим индекс
        $parts = [];
        $chunks = array_chunk($urlNodes, max(1, $maxUrls));
        foreach ($chunks as $i => $chunk) {
            $partPath = sprintf('%s/images-sitemap-%d.xml', $outputDir, $i + 1);
            $this->atomicWrite($partPath, $this->renderUrlset($chunk));
            $parts[] = $partPath;
        }

        $indexXml = $this->renderIndex($parts, $base);
        $this->atomicWrite($indexPath, $indexXml);

        return [
            'sitemap'       => $indexPath,
            'count_urls'    => $totalUrls,
            'count_images'  => $totalImages,
            'indexed'       => true,
            'parts'         => $parts,
        ];
    }

    /* ================= helpers ================= */

    protected function renderUrlset(array $urlNodes): string
    {
        $items = [];
        foreach ($urlNodes as $node) {
            $loc = $this->esc($node['loc']);
            $imgs = [];
            foreach ($node['images'] as $img) {
                $locImg = $this->esc($img['loc']);
                $cap = isset($img['caption']) && $img['caption'] !== ''
                    ? '<image:caption>'.$this->esc($img['caption']).'</image:caption>'
                    : '';
                $tit = isset($img['title']) && $img['title'] !== ''
                    ? '<image:title>'.$this->esc($img['title']).'</image:title>'
                    : '';
                $imgs[] = '<image:image><image:loc>'.$locImg.'</image:loc>'.$cap.$tit.'</image:image>';
            }
            $items[] = '<url><loc>'.$loc.'</loc>'.implode('', $imgs).'</url>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
             . 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'
             . implode('', $items)
             . '</urlset>';
    }

    /**
     * Индекс для частичных карт: ссылки абсолютные.
     * @param array<int,string> $partPaths
     */
    protected function renderIndex(array $partPaths, string $base): string
    {
        $items = [];
        foreach ($partPaths as $p) {
            $mtime = @filemtime($p) ?: time();
            $file  = basename($p);
            $loc   = $this->esc($base . '/sitemaps/' . $file);
            $last  = $this->esc(gmdate('c', $mtime));
            $items[] = "<sitemap><loc>{$loc}</loc><lastmod>{$last}</lastmod></sitemap>";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
             . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
             . implode('', $items)
             . '</sitemapindex>';
    }

    protected function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    protected function absUrl(string $u, string $base): string
    {
        $u = trim($u);
        if ($u === '') return '';
        if (preg_match('~^https?://~i', $u)) return $u;
        return $base . '/' . ltrim($u, '/');
    }

    protected function validUrl(string $u): bool
    {
        if ($u === '') return false;
        if (!preg_match('~^https?://~i', $u)) return false; // отсечь data:, ftp: и пр.
        return (bool) filter_var($u, FILTER_VALIDATE_URL);
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
