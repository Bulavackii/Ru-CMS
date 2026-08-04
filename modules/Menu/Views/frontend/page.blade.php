@extends('layouts.frontend')

@section('title', $title)

@section('content')
    @php
        // reading_time() считает слова с поддержкой кириллицы (см. app/helpers.php)
        $readMins = reading_time($page->t('content'));

        $html = render_shortcodes($page->t('content'));

        // Оглавление собирается из заголовков второго уровня, которые редактор
        // расставил в тексте. Якоря проставляются здесь же: требовать от
        // редактора вписывать id вручную в визуальном редакторе нельзя.
        //
        // Транслитерация нужна, потому что id из кириллицы работает в браузере,
        // но ломается в ссылке: адрес превращается в проценты, и поделиться им
        // уже неудобно.
        $toc = [];

        $html = preg_replace_callback('~<h2([^>]*)>(.*?)</h2>~su', function ($m) use (&$toc) {
            $text = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8'));

            if ($text === '') {
                return $m[0];
            }

            $slug = \Illuminate\Support\Str::slug($text) ?: 'razdel-' . (count($toc) + 1);
            $toc[] = ['id' => $slug, 'text' => $text];

            // Собственный id из редактора уважаем — вдруг на него уже ссылались.
            $attrs = str_contains($m[1], 'id=') ? $m[1] : $m[1] . ' id="' . e($slug) . '"';

            return '<h2' . $attrs . '>' . $m[2] . '</h2>';
        }, $html);
    @endphp

    <article class="w-full max-w-screen-2xl mx-auto">

        {{-- ===== Шапка страницы ===== --}}
        <header class="fx-card p-6 sm:p-8 md:p-10 mb-6">
            {{-- Хлебные крошки --}}
            <nav class="flex items-center flex-wrap gap-1.5 text-xs text-gray-500 dark:text-gray-400 mb-5" aria-label="Хлебные крошки">
                <a href="{{ url('/') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors inline-flex items-center gap-1">
                    @themeIcon('home') Главная
                </a>
                <span class="opacity-50">/</span>
                <span class="text-gray-400 dark:text-gray-500">Страницы</span>
                <span class="opacity-50">/</span>
                <span class="text-gray-700 dark:text-gray-300 truncate max-w-[16rem]">{{ $page->t('title') }}</span>
            </nav>

            <div class="flex items-start gap-3 sm:gap-4">
                <span class="fx-badge shrink-0 mt-1"><i class="fas fa-file-lines"></i></span>
                <h1 class="fx-section-title text-2xl sm:text-3xl md:text-4xl leading-tight break-words">
                    {{ $page->t('title') }}
                </h1>
            </div>

            {{-- Мета: дата обновления · время чтения · категории --}}
            <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500 dark:text-gray-400">
                @if ($page->updated_at)
                    <span class="inline-flex items-center gap-1.5">
                        <i class="far fa-calendar-alt fx-ico"></i> Обновлено {{ $page->updated_at->format('d.m.Y') }}
                    </span>
                @endif
                <span class="inline-flex items-center gap-1.5">
                    <i class="far fa-clock fx-ico"></i> ~{{ $readMins }} мин чтения
                </span>
                @if ($page->categories->isNotEmpty())
                    <span class="inline-flex flex-wrap items-center gap-1.5">
                        @foreach ($page->categories as $cat)
                            <a href="{{ url('/?category=' . $cat->id) }}" class="fx-chip hover:brightness-95">{{ $cat->title }}</a>
                        @endforeach
                    </span>
                @endif
            </div>
        </header>

        {{-- ===== Оглавление =====
             Показывается только с трёх разделов: у короткой страницы оно
             занимает больше места, чем экономит. --}}
        @if (count($toc) >= 3)
            <nav class="fx-card pc-toc p-5 sm:p-6 mb-6" aria-label="Содержание страницы">
                <p class="pc-toc__title">Содержание</p>
                <ol class="pc-toc__list">
                    @foreach ($toc as $item)
                        <li><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        @endif

        {{-- ===== Контент ===== --}}
        <div class="fx-card p-6 sm:p-8 md:p-10 mb-6">
            <div class="prose prose-sm sm:prose lg:prose-lg dark:prose-invert max-w-none page-content text-gray-800 dark:text-gray-100">
                {{-- Шорткоды уже развёрнуты выше, там же расставлены якоря --}}
                {!! $html !!}
            </div>
        </div>

        {{-- ===== Действия ===== --}}
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <a href="{{ url('/') }}" class="fx-btn px-5 py-2.5 text-sm">
                <i class="fas fa-arrow-left"></i> На главную
            </a>

            {{-- Поделиться --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">Поделиться:</span>
                <a href="https://vk.com/share.php?url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener"
                   class="share-btn share-btn--plain" style="--c:#0077FF" title="ВКонтакте" aria-label="Поделиться во ВКонтакте"><x-icon.vk :size="16" /></a>
                <a href="https://max.ru/share?url={{ urlencode(url()->current()) }}&text={{ urlencode($page->t('title')) }}" target="_blank" rel="noopener" class="share-btn share-btn--plain" style="--c:#3B4BF5" title="MAX" aria-label="Поделиться в MAX"><x-icon.max :size="16" /></a>
                <button type="button" class="share-btn copy-link" data-url="{{ url()->current() }}" style="--c:#6366f1"
                        title="Скопировать ссылку" aria-label="Скопировать ссылку"><i class="fas fa-link"></i></button>
            </div>
        </div>
    </article>

    <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-2"></div>
@endsection

@push('scripts')
<script>
    // Копирование ссылки на страницу + короткий тост
    function pageToast(message, isError = false) {
        const box = document.getElementById('toast-container');
        if (!box) return;
        const t = document.createElement('div');
        t.className = 'px-4 py-2.5 shadow text-sm font-medium text-white';
        t.style.background = isError ? '#e11d48' : '#4f46e5';
        t.textContent = message;
        box.appendChild(t);
        setTimeout(() => t.remove(), 2200);
    }

    document.querySelectorAll('.copy-link').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = btn.dataset.url || location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url)
                    .then(() => pageToast('Ссылка скопирована'))
                    .catch(() => pageToast('Не удалось скопировать', true));
            } else {
                pageToast('Копирование недоступно', true);
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* Контент страницы: читаемая ширина внутри карточки на всю ширину */
    .page-content{ word-break:break-word; overflow-wrap:anywhere; line-height:1.8; font-size:1.06rem;
        max-width:70rem !important; margin-inline:auto; }
    .page-content > *:first-child{ margin-top:0; }
    .page-content a{ color:var(--color-primary,#6366f1); text-decoration:underline; text-underline-offset:2px; word-break:break-word; }
    .page-content a:hover{ filter:brightness(1.1); }
    .page-content h2, .page-content h3{ color:#111827; }
    :root.dark .page-content h2, :root.dark .page-content h3{ color:#f3f4f6; }
    .page-content pre{ white-space:pre-wrap; word-break:break-word; background:#0f172a; color:#e5e7eb;
        padding:1rem 1.15rem; overflow-x:auto; font-size:.9rem; }
    .page-content table{ width:100%; display:block; overflow-x:auto; }
    @media (max-width:640px){ .page-content{ font-size:.98rem; } }

    /* Медиа из редактора: адаптив + поддержка float, выставленного в TinyMCE */
    .page-content img, .page-content video, .page-content iframe,
    .page-content embed, .page-content object{
        display:inline-block; max-width:100%; height:auto; margin:1.5rem auto;
        box-shadow:0 10px 28px -14px rgba(17,24,39,.4);
    }
    .page-content img[style*="float:left"], .page-content video[style*="float:left"],
    .page-content iframe[style*="float:left"], .page-content embed[style*="float:left"],
    .page-content object[style*="float:left"], .page-content img[style*="float: left"],
    .page-content video[style*="float: left"], .page-content iframe[style*="float: left"],
    .page-content embed[style*="float: left"], .page-content object[style*="float: left"]{
        float:left; margin-right:1rem; margin-left:0;
    }
    .page-content img[style*="float:right"], .page-content video[style*="float:right"],
    .page-content iframe[style*="float:right"], .page-content embed[style*="float:right"],
    .page-content object[style*="float:right"], .page-content img[style*="float: right"],
    .page-content video[style*="float: right"], .page-content iframe[style*="float: right"],
    .page-content embed[style*="float: right"], .page-content object[style*="float: right"]{
        float:right; margin-left:1rem; margin-right:0;
    }
    .page-content:after{ content:""; display:table; clear:both; }

    /* Кнопки «Поделиться» */
    .share-btn{ display:inline-flex; align-items:center; justify-content:center; width:2.3rem; height:2.3rem;
        border:1px solid rgba(17,24,39,.12); background:rgba(255,255,255,.6); color:#6b7280; font-size:1rem;
        text-decoration:none; cursor:pointer;
        transition:color .15s ease, background .15s ease, border-color .15s ease, transform .15s ease; }
    :root.dark .share-btn{ border-color:rgba(255,255,255,.12); background:rgba(30,41,59,.5); color:#9ca3af; }
    .share-btn:hover{ color:#fff; background:var(--c,#6366f1); border-color:var(--c,#6366f1); transform:translateY(-2px); }
    /* У MAX собственный цветной глиф — фон кнопки при наведении не
       закрашиваем, иначе фирменный знак теряется. */
    .share-btn--plain{ padding:.25rem; background:transparent; }
    .share-btn--plain:hover{ background:transparent; border-color:var(--c,#6366f1); }
    :root.dark .share-btn--plain{ background:transparent; }

    /* ══════════════════════════════════════════════════════════════════
       БЛОКИ СОДЕРЖИМОГО

       Готовые оформления для текста страницы. Редактор применяет их из
       визуального редактора — достаточно поставить класс блоку, никакого
       CSS писать не нужно:

         pc-lead    — вводный абзац крупнее обычного
         pc-grid    — сетка из блоков pc-card (2 колонки, на телефоне 1)
         pc-card    — карточка с заголовком и текстом внутри сетки
         pc-check   — список с галочками вместо точек
         pc-note    — выделенная врезка-примечание
         pc-cta     — полоса призыва к действию со ссылкой внутри

       Классы литеральные: в статической сборке Tailwind нет ни прозрачности
       через дробь, ни произвольных значений. Цвета — из активной темы.
       ══════════════════════════════════════════════════════════════════ */

    .page-content .pc-lead{ font-size:1.05rem; line-height:1.65; color:#334155 }
    :root.dark .page-content .pc-lead{ color:#cbd5e1 }

    .page-content .pc-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(17rem,1fr));
        gap:1rem; margin:1.5rem 0; padding:0; list-style:none }

    .page-content .pc-card{ margin:0; padding:1.1rem 1.25rem; background:#fff;
        border:1px solid #eef2f7; transition:border-color .15s, transform .15s }
    .page-content .pc-card:hover{ border-color:var(--color-primary,#6366f1); transform:translateY(-2px) }
    .page-content .pc-card > :first-child{ margin-top:0 }
    .page-content .pc-card > :last-child{ margin-bottom:0 }
    .page-content .pc-card strong,
    .page-content .pc-card h3{ display:block; margin-bottom:.35rem; font-size:1rem;
        font-weight:700; color:#111827 }
    :root.dark .page-content .pc-card{ background:#111827; border-color:#1f2937 }
    :root.dark .page-content .pc-card strong,
    :root.dark .page-content .pc-card h3{ color:#f3f4f6 }

    .page-content .pc-check{ list-style:none; padding:0; margin:1.25rem 0;
        display:grid; grid-template-columns:repeat(auto-fit,minmax(18rem,1fr)); gap:.6rem 1.5rem }
    .page-content .pc-check li{ position:relative; padding-left:1.6rem; margin:0; line-height:1.5 }
    .page-content .pc-check li::before{ content:'✓'; position:absolute; left:0; top:0;
        font-weight:700; color:var(--color-primary,#6366f1) }

    /* Врезка: слева акцентная полоса, чтобы её нельзя было спутать с текстом. */
    .page-content .pc-note{ margin:1.5rem 0; padding:1rem 1.25rem; background:#f8fafc;
        border-left:3px solid var(--color-primary,#6366f1) }
    .page-content .pc-note > :first-child{ margin-top:0 }
    .page-content .pc-note > :last-child{ margin-bottom:0 }
    :root.dark .page-content .pc-note{ background:#0b1220 }

    .page-content .pc-cta{ display:flex; align-items:center; justify-content:space-between;
        gap:1rem; flex-wrap:wrap; margin:1.75rem 0 .5rem; padding:1.25rem 1.5rem; color:#fff;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }
    .page-content .pc-cta > :first-child{ margin:0 }
    .page-content .pc-cta a{ flex:none; padding:.6rem 1.35rem; font-weight:700;
        color:#111827; background:#fff; text-decoration:none }
    .page-content .pc-cta a:hover{ color:var(--color-primary,#6366f1) }

    /* ── Оглавление ─────────────────────────────────────────────────── */
    .pc-toc__title{ margin:0 0 .6rem; font-size:.75rem; font-weight:700;
        letter-spacing:.08em; text-transform:uppercase; color:#94a3b8 }
    .pc-toc__list{ margin:0; padding:0; list-style:none; display:grid;
        grid-template-columns:repeat(auto-fit,minmax(15rem,1fr)); gap:.4rem 1.5rem;
        counter-reset:pc-toc }
    .pc-toc__list li{ counter-increment:pc-toc }
    .pc-toc__list a{ display:inline-flex; gap:.5rem; font-size:.9rem; color:#334155;
        text-decoration:none; line-height:1.4 }
    .pc-toc__list a::before{ content:counter(pc-toc) '.'; flex:none; font-weight:700;
        color:var(--color-primary,#6366f1) }
    .pc-toc__list a:hover{ color:var(--color-primary,#6366f1) }
    :root.dark .pc-toc__list a{ color:#cbd5e1 }

    /* Якорь не должен уезжать под шапку при переходе из оглавления. */
    .page-content h2[id]{ scroll-margin-top:5rem }

    /* ── Иконка в карточке ──────────────────────────────────────────── */
    /* Значок — обычная иконка Font Awesome внутри квадрата с градиентом.
       Редактор меняет только имя иконки в классе, оформление остаётся. */
    .page-content .pc-ico{ display:inline-flex; align-items:center; justify-content:center;
        width:2.4rem; height:2.4rem; margin-bottom:.75rem; flex:none;
        font-size:.95rem; color:#fff;
        background:linear-gradient(135deg,var(--color-primary,#6366f1),var(--color-accent,#8b5cf6)) }

    /* ── Цифры ──────────────────────────────────────────────────────── */
    .page-content .pc-stats{ display:grid; grid-template-columns:repeat(auto-fit,minmax(9rem,1fr));
        gap:1px; margin:1.75rem 0; padding:0; list-style:none; background:#e2e8f0;
        border:1px solid #e2e8f0 }
    .page-content .pc-stats li{ margin:0; padding:1.1rem .9rem; text-align:center; background:#fff }
    .page-content .pc-stats b{ display:block; font-size:1.75rem; line-height:1.1; font-weight:800;
        color:var(--color-primary,#6366f1); font-variant-numeric:tabular-nums }
    .page-content .pc-stats span{ display:block; margin-top:.3rem; font-size:.8rem; color:#64748b }
    :root.dark .page-content .pc-stats{ background:#1f2937; border-color:#1f2937 }
    :root.dark .page-content .pc-stats li{ background:#111827 }
    :root.dark .page-content .pc-stats span{ color:#94a3b8 }

    /* ── Шаги ───────────────────────────────────────────────────────── */
    /* Номер рисуется счётчиком: редактор просто добавляет пункт списка,
       перенумеровывать руками ничего не нужно. */
    .page-content .pc-steps{ counter-reset:pc-step; list-style:none; padding:0; margin:1.5rem 0;
        display:grid; grid-template-columns:repeat(auto-fit,minmax(15rem,1fr)); gap:1rem }
    .page-content .pc-steps li{ counter-increment:pc-step; position:relative; margin:0;
        padding:1.1rem 1.25rem 1.1rem 3.4rem; background:#fff; border:1px solid #eef2f7 }
    .page-content .pc-steps li::before{ content:counter(pc-step); position:absolute;
        left:1.1rem; top:1.05rem; display:flex; align-items:center; justify-content:center;
        width:1.7rem; height:1.7rem; font-size:.8rem; font-weight:800; color:#fff;
        background:var(--color-primary,#6366f1) }
    .page-content .pc-steps strong{ display:block; margin-bottom:.2rem; color:#111827 }
    :root.dark .page-content .pc-steps li{ background:#111827; border-color:#1f2937 }
    :root.dark .page-content .pc-steps strong{ color:#f3f4f6 }

    /* ── Текст и картинка рядом ─────────────────────────────────────── */
    .page-content .pc-split{ display:grid; grid-template-columns:1fr 1fr; align-items:center;
        gap:1.75rem; margin:1.75rem 0 }
    .page-content .pc-split > div > :first-child{ margin-top:0 }
    .page-content .pc-split img{ width:100%; height:auto; display:block; margin:0;
        border:1px solid #eef2f7 }
    @media (max-width:768px){
        .page-content .pc-split{ grid-template-columns:1fr; gap:1.1rem }
    }
    :root.dark .page-content .pc-split img{ border-color:#1f2937 }

    /* ── Технологии ─────────────────────────────────────────────────── */
    .page-content .pc-tech{ display:flex; flex-wrap:wrap; gap:.45rem; margin:1.25rem 0;
        padding:0; list-style:none }
    .page-content .pc-tech li{ margin:0; padding:.35rem .8rem; font-size:.8rem; font-weight:600;
        color:#4338ca; background:#eef2ff; border:1px solid #e0e7ff }
    :root.dark .page-content .pc-tech li{ color:#c7d2fe; background:#1e1b4b; border-color:#312e81 }

    @media (max-width:640px){
        /* Четыре числа в столбик занимают пол-экрана и перестают читаться
           как один блок — на телефоне их две пары. */
        .page-content .pc-stats{ grid-template-columns:1fr 1fr }
        .page-content .pc-stats b{ font-size:1.5rem }

        /* Кнопка призыва не помещалась в строку и выходила за край карточки:
           на узком экране блок складывается, кнопка занимает всю ширину. */
        .page-content .pc-cta{ flex-direction:column; align-items:stretch; text-align:center }
        .page-content .pc-cta a{ text-align:center }
    }
</style>
@endpush
