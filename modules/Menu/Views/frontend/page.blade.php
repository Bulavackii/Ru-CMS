@extends('layouts.frontend')

@section('title', $title)

@section('content')
    @php
        // reading_time() считает слова с поддержкой кириллицы (см. app/helpers.php)
        $readMins = reading_time($page->t('content'));
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

        {{-- ===== Контент ===== --}}
        <div class="fx-card p-6 sm:p-8 md:p-10 mb-6">
            <div class="prose prose-sm sm:prose lg:prose-lg dark:prose-invert max-w-none page-content text-gray-800 dark:text-gray-100">
                {{-- Шорткоды из редактора (например, вставленная каптча) --}}
                {!! render_shortcodes($page->t('content')) !!}
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
                   class="share-btn" style="--c:#0077FF" title="ВКонтакте" aria-label="Поделиться во ВКонтакте"><i class="fab fa-vk"></i></a>
                <a href="https://t.me/share/url?url={{ urlencode(url()->current()) }}&text={{ urlencode($page->t('title')) }}" target="_blank" rel="noopener"
                   class="share-btn" style="--c:#26A5E4" title="Telegram" aria-label="Поделиться в Telegram"><i class="fab fa-telegram"></i></a>
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
</style>
@endpush
