<?php

namespace Modules\Seo\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Seo\Models\SeoPage;

/**
 * 🔎 Данные раздела SEO для текущей страницы сайта.
 *
 * До 26.07.2026 раздел «SEO — страницы» вообще не влиял на сайт: лейаут
 * фронта брал title/description из полей самих новостей и страниц, тег robots
 * был жёстко «index, follow», canonical — всегда текущий URL, а og-поля и
 * JSON-LD не выводились никогда. То есть заполнить SEO-запись было можно,
 * но увидеть результат — нет.
 *
 * Резолвер ищет запись по адресу текущей страницы и отдаёт готовый набор
 * значений. Если записи нет, возвращаются пустые значения, и лейаут
 * использует прежние источники — поведение страниц без SEO-записи не меняется.
 */
class SeoMetaResolver
{
    /** Сколько держим найденную запись в кеше (секунды) */
    private const TTL = 300;

    public function forRequest(Request $request): array
    {
        $path = '/' . ltrim($request->path(), '/');
        if ($path !== '/' ) {
            $path = rtrim($path, '/');
        }

        $page = Cache::remember(
            'seo_meta_v' . SeoPage::metaCacheVersion() . '_' . md5($path),
            self::TTL,
            fn () => SeoPage::query()->where('slug', $path)->first()
        );

        if (! $page) {
            return $this->emptyMeta();
        }

        $og = is_array($page->og) ? $page->og : [];

        return [
            'has'         => true,
            'title'       => $page->title ?: null,
            'description' => $page->description ?: null,
            'keywords'    => $page->keywords ?: null,
            'h1'          => $page->h1 ?: null,
            'canonical'   => $page->canonical ?: null,
            // robots_index/robots_follow из админки наконец доезжают до тега
            'robots'      => ($page->robots_index ? 'index' : 'noindex')
                . ', ' . ($page->robots_follow ? 'follow' : 'nofollow'),
            'og'          => $og,
            'jsonld'      => is_array($page->jsonld) && $page->jsonld !== [] ? $page->jsonld : null,
        ];
    }

    private function emptyMeta(): array
    {
        return [
            'has'         => false,
            'title'       => null,
            'description' => null,
            'keywords'    => null,
            'h1'          => null,
            'canonical'   => null,
            'robots'      => null,
            'og'          => [],
            'jsonld'      => null,
        ];
    }
}
