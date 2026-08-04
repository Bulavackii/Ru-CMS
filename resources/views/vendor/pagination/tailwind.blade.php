{{--
    Постраничная навигация — ОДНА на весь проект.

    Этот файл рендерят все вызовы links() и в панели, и на сайте: часть вьюх
    зовёт его как pagination::tailwind, часть по пути vendor.pagination.tailwind,
    но файл один и тот же. Правка здесь видна везде.

    Стили литеральные, не Tailwind: в статической сборке проекта нет ни
    прозрачности через дробь, ни произвольных значений, а скругления на фронте
    и в панели вдобавок принудительно сняты глобальным правилом. Цвет активной
    страницы берётся из активной темы.

    Необязательный параметр summary — готовая подпись слева вместо счётчика
    записей. Нужен там, где постранично разбиваются не сами записи: на списке
    новостей страница набирается целыми группами-шаблонами, и «показано с 1 по
    15 из 5» было бы бессмыслицей. Передаётся вторым аргументом в links().
--}}

@once
@push('styles')
<style>
    .pg{ display:flex; align-items:center; justify-content:space-between; gap:1rem;
        flex-wrap:wrap; width:100%; font-size:.875rem }

    .pg__info{ color:#64748b; line-height:1.4 }
    .pg__info b{ font-weight:600; color:#0f172a }

    .pg__list{ display:flex; align-items:center; gap:.35rem; margin:0 auto; padding:0; list-style:none }

    /* 40px — минимум, за который уверенно попадают пальцем. */
    .pg__link{ display:inline-flex; align-items:center; justify-content:center;
        min-width:40px; height:40px; padding:0 .7rem; font-weight:600; color:#334155;
        background:#fff; border:1px solid #e2e8f0; text-decoration:none;
        transition:border-color .15s, color .15s, background-color .15s }
    .pg__link:hover{ border-color:var(--color-primary,#6366f1); color:var(--color-primary,#6366f1) }
    .pg__link:focus-visible{ outline:2px solid var(--color-primary,#6366f1); outline-offset:2px }

    .pg__link--active{ color:#fff; background:var(--color-primary,#6366f1);
        border-color:var(--color-primary,#6366f1); cursor:default }
    .pg__link--active:hover{ color:#fff }

    .pg__link--off{ color:#cbd5e1; background:#f8fafc; cursor:not-allowed }
    .pg__link--off:hover{ border-color:#e2e8f0; color:#cbd5e1 }

    .pg__gap{ display:inline-flex; align-items:center; justify-content:center;
        min-width:24px; height:40px; color:#94a3b8; letter-spacing:.1em }

    /* Подпись у стрелок шире самой стрелки — на узком экране прячется. */
    .pg__word{ margin:0 .3rem }

    @media (max-width:640px){
        .pg{ justify-content:center }
        .pg__info{ width:100%; text-align:center; order:2 }
        .pg__list{ order:1 }
        .pg__word{ display:none }
        /* Номера страниц на телефоне съедают строку: остаются края, текущая
           и её соседи. Отметку разрыва тоже прячем — без номеров она не нужна. */
        .pg__num--far{ display:none }
        .pg__gap{ display:none }
    }

    :root.dark .pg__info{ color:#94a3b8 }
    :root.dark .pg__info b{ color:#e2e8f0 }
    :root.dark .pg__link{ color:#cbd5e1; background:#111827; border-color:#1f2937 }
    :root.dark .pg__link--off{ color:#475569; background:#0b1220 }
    :root.dark .pg__link--active{ color:#fff }
</style>
@endpush
@endonce

@if ($paginator->hasPages())
    @php
        // Текущая, её соседи и края видны всегда; остальные номера на телефоне
        // прячутся, иначе ряд не помещается в строку.
        $near = fn ($page) => abs($page - $paginator->currentPage()) <= 1
            || $page === 1
            || $page === $paginator->lastPage();
    @endphp

    <nav class="pg" role="navigation" aria-label="Постраничная навигация">
        <div class="pg__info">
            @isset($summary)
                {{ $summary }}
            @else
                Показано
                <b>{{ $paginator->firstItem() }}</b>–<b>{{ $paginator->lastItem() }}</b>
                из <b>{{ $paginator->total() }}</b>
            @endisset
        </div>

        <ul class="pg__list">
            {{-- Назад --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="pg__link pg__link--off" aria-disabled="true">
                        &lsaquo;<span class="pg__word">Назад</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                       class="pg__link" aria-label="Предыдущая страница">
                        &lsaquo;<span class="pg__word">Назад</span>
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                {{-- Разрыв в ряду номеров --}}
                @if (is_string($element))
                    <li><span class="pg__gap" aria-hidden="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="pg__link pg__link--active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}"
                                   class="pg__link{{ $near($page) ? '' : ' pg__num--far' }}"
                                   aria-label="Страница {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Вперёд --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                       class="pg__link" aria-label="Следующая страница">
                        <span class="pg__word">Вперёд</span>&rsaquo;
                    </a>
                @else
                    <span class="pg__link pg__link--off" aria-disabled="true">
                        <span class="pg__word">Вперёд</span>&rsaquo;
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
