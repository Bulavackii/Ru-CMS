<?php

namespace Modules\Visual\Support;

use Illuminate\Support\Facades\View;
use Modules\Visual\Models\Fragment;

class FragmentRenderer
{
    /**
     * Рендер фрагмента по slug или zone.
     * @param array $opts ['slug'=>..., 'zone'=>..., 'view'=>..., 'data'=>...]
     */
    public static function render(array $opts = []): string
    {
        $slug = $opts['slug'] ?? null;
        $zone = $opts['zone'] ?? null;

        $query = Fragment::query()->where('is_active', true);
        if ($slug) $query->where('slug', $slug);
        elseif ($zone) $query->where('zone', $zone);
        else return '<!-- visual: no slug/zone provided -->';

        /** @var Fragment|null $frag */
        $frag = $query->first();
        if (!$frag) {
            return '<!-- visual: fragment not found -->';
        }

        // 1) Если есть html_cached — отдаём (самый быстрый путь)
        if (!empty($frag->html_cached)) {
            return $frag->html_cached;
        }

        // 2) Иначе пробуем собрать из partial (по convention)
        // convention: resources/views/visual/fragments/{slug}.blade.php
        $viewName = $opts['view'] ?? ('visual.fragments.' . $frag->slug);
        $data = array_merge(['fragment' => $frag], ($opts['data'] ?? []));

        if (View::exists($viewName)) {
            return view($viewName, $data)->render();
        }

        // 3) fallback — простая обёртка из JSON data
        $title = e($frag->title);
        $body  = e(json_encode($frag->data));
        return "<!-- fallback -->\n<div class=\"visual-fragment\" data-fragment=\"{$frag->slug}\"><strong>{$title}</strong></div>";
    }
}
