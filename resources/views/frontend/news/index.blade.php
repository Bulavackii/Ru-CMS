@extends('layouts.frontend')

@section('title', __('frontend.news.title'))

@section('content')
    @php
        // Материалы разных шаблонов раньше валились в одну кучу: обзор игры,
        // услуга клиники и товар выглядели одинаковыми карточками подряд.
        // Группируем по шаблону — у каждой группы свой заголовок и своя
        // вёрстка, ровно как на главной.
        $labels = \Modules\News\Controllers\Admin\NewsController::TEMPLATES;

        $groups = $newsList->getCollection()->groupBy(fn ($item) => $item->template ?: 'default');

        // Порядок групп — как в общем списке шаблонов, чтобы он не прыгал
        // от страницы к странице.
        $ordered = collect(array_keys($labels))
            ->filter(fn ($key) => $groups->has($key))
            ->values();
    @endphp

    <div class="nw-page">
        @foreach ($ordered as $key)
            @php
                $view = 'frontend.templates.' . $key;
                $items = $groups->get($key);
            @endphp

            {{-- Шаблон группы, если он есть; иначе обычная лента новостей --}}
            @include(view()->exists($view) ? $view : 'frontend.templates.default', [
                'newsList' => $items,
                'title' => $labels[$key] ?? __('frontend.news.title'),
            ])
        @endforeach

        {{-- Пагинация одна на всю страницу, а не по группе --}}
        @if ($newsList->hasPages())
            <div class="nw-page__pager">{{ $newsList->links() }}</div>
        @endif
    </div>
@endsection

@push('styles')
<style>
    /* Разделитель между группами: без него блоки разных шаблонов
       сливались в сплошную ленту. */
    .nw-page > section + section{ border-top:1px solid rgba(17,24,39,.08); padding-top:1.5rem }
    .nw-page__pager{ margin:2rem 0 1rem; display:flex; justify-content:center }

    @media (prefers-color-scheme: dark){
        .nw-page > section + section{ border-color:rgba(255,255,255,.08) }
    }
</style>
@endpush
