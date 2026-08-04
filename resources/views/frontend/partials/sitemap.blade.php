{{--
    Живая карта сайта.

    Вставляется шорткодом [sitemap] прямо из визуального редактора. Список
    собирается из БАЗЫ при каждом заходе, поэтому не устаревает: добавили
    страницу или раздел — они появились здесь сами.

    Прежняя карта была списком ссылок, вписанным руками, и разошлась с
    сайтом: в ней остались «Концепция», «О компании», «Прайс-лист» и
    «Выполненные работы» — адресов с таким содержимым уже не существует.
--}}

@php
    // Страницы раздела «Страницы» — только опубликованные.
    $sitemapPages = \Modules\Menu\Models\Page::query()
        ->where('published', true)
        ->orderBy('homepage_order')
        ->orderBy('title')
        ->get(['title', 'slug']);

    // Разделы ленты — те шаблоны, по которым реально есть материалы.
    $sitemapTemplates = \Modules\News\Controllers\Admin\NewsController::TEMPLATES;

    $sitemapSections = \Modules\News\Models\News::query()
        ->where('published', true)
        ->select('template')
        ->distinct()
        ->pluck('template')
        ->map(fn ($t) => filled($t) ? $t : 'default')
        ->unique()
        ->filter(fn ($t) => isset($sitemapTemplates[$t]))
        ->values();

    // Служебные адреса, которых нет ни в страницах, ни в разделах.
    $sitemapService = [
        ['url' => '/',       'title' => 'Главная'],
        ['url' => '/news',   'title' => 'Новости'],
        ['url' => '/search', 'title' => 'Поиск по сайту'],
    ];
@endphp

<div class="pc-sitemap">
    <section class="pc-sitemap__group">
        <h3>Основное</h3>
        <ul>
            @foreach ($sitemapService as $link)
                <li><a href="{{ url($link['url']) }}">{{ $link['title'] }}</a></li>
            @endforeach
        </ul>
    </section>

    @if ($sitemapPages->isNotEmpty())
        <section class="pc-sitemap__group">
            <h3>Страницы</h3>
            <ul>
                @foreach ($sitemapPages as $page)
                    <li><a href="{{ url('/page/' . $page->slug) }}">{{ $page->t('title') }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($sitemapSections->isNotEmpty())
        <section class="pc-sitemap__group">
            <h3>Разделы ленты</h3>
            <ul>
                @foreach ($sitemapSections as $key)
                    {{-- Якорь ведёт к нужному разделу на странице новостей:
                         там материалы сгруппированы по шаблонам. --}}
                    <li><a href="{{ url('/news') }}">{{ $sitemapTemplates[$key] }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
