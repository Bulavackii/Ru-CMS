<?php
namespace Modules\Seo\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NewsSitemapBuilder
{
    /**
     * Элемент входного массива:
     * [
     *   'loc'              => string,                     // URL статьи (может быть относительным)
     *   'title'            => string,                     // заголовок
     *   'publication_date' => string|\DateTimeInterface,  // дата публикации
     *   'publication_name' => ?string,                    // по умолчанию config('app.name')
     *   'genres'           => ?string,                    // необязательно
     *   'keywords'         => ?string|string[],           // необязательно
     * ]
     *
     * @param array<int, array<string, mixed>> $items
     * @param string|null $outputDir Куда писать файл; если null — из config('seo.sitemaps.output_dir')
     * @return string Путь к созданному файлу
     */
    public function build(array $items, string $outputDir = null): string
    {
        $outputDir = rtrim((string)($outputDir ?: config('seo.sitemaps.output_dir', public_path('sitemaps'))), '/');
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0775, true);
        }

        $base = rtrim((string)config('app.url'), '/');
        if ($base === '') {
            $base = rtrim((string)request()->getSchemeAndHttpHost(), '/');
        }

        // Только материалы за последние 48 часов
        $now      = Carbon::now();
        $cutoff   = $now->copy()->subHours(48);
        $maxItems = (int) config('seo.news_sitemap.max_items', 1000);
        $lang     = (string) config('seo.news_sitemap.language', 'ru');

        $xmlItems = [];
        $seen     = []; // дедуп по loc

        foreach ($items as $it) {
            $locRaw = (string)($it['loc'] ?? '');
            $loc    = $this->absUrl($locRaw, $base);

            $titleRaw = (string)($it['title'] ?? '');
            $title    = trim(strip_tags($titleRaw));
            // В новостном sitemap лучше держать заголовки умеренной длины
            $title    = Str::limit($title, 200);

            $dateIso  = $this->toIso8601($it['publication_date'] ?? null);

            if (!$this->validUrl($loc) || $title === '' || !$dateIso) {
                continue;
            }

            // Фильтр по 48 часам
            try {
                $d = Carbon::parse($dateIso);
                if ($d->lt($cutoff)) continue;
            } catch (\Throwable $e) {
                continue;
            }

            // Дедуп по абсолютному URL
            if (isset($seen[$loc])) continue;
            $seen[$loc] = true;

            $name   = $this->esc((string)($it['publication_name'] ?? config('app.name', 'Site')));
            $genres = isset($it['genres']) && $it['genres'] !== ''
                ? '<news:genres>'.$this->esc((string)$it['genres']).'</news:genres>'
                : '';

            // Опциональные keywords (строка или массив)
            $keywords = '';
            if (array_key_exists('keywords', $it) && $it['keywords'] !== null && $it['keywords'] !== '') {
                $kw = $it['keywords'];
                if (is_array($kw)) {
                    $kw = array_filter(array_map(fn($v) => trim((string)$v), $kw), fn($v) => $v !== '');
                    $kw = implode(', ', $kw);
                } else {
                    $kw = trim((string)$kw);
                }
                if ($kw !== '') {
                    $keywords = '<news:keywords>'.$this->esc($kw).'</news:keywords>';
                }
            }

            $xmlItems[] =
                '<url>'
              .     '<loc>'.$this->esc($loc).'</loc>'
              .     '<news:news>'
              .         '<news:publication>'
              .             '<news:name>'.$name.'</news:name>'
              .             '<news:language>'.$this->esc($lang).'</news:language>'
              .         '</news:publication>'
              .         $genres
              .         '<news:publication_date>'.$this->esc($dateIso).'</news:publication_date>'
              .         '<news:title>'.$this->esc($title).'</news:title>'
              .         $keywords
              .     '</news:news>'
              . '</url>';

            if (count($xmlItems) >= $maxItems) break;
        }

        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>'
          . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
          .         'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'
          . implode('', $xmlItems)
          . '</urlset>';

        $path = $outputDir . '/news-sitemap.xml';
        $this->atomicWrite($path, $xml);

        return $path;
    }

    /* ---------- helpers ---------- */

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
        if (!preg_match('~^https?://~i', $u)) return false;
        return (bool) filter_var($u, FILTER_VALIDATE_URL);
    }

    /**
     * Приводим дату к ISO-8601 с таймзоной (пример: 2025-09-14T12:34:56+03:00).
     * @param mixed $value
     */
    protected function toIso8601($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->toIso8601String();
        }
        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->toIso8601String();
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
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
