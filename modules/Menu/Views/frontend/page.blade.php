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
{{-- Поведение блока «Вопросы и ответы».

     Сам аккордеон — нативный тег details, он раскрывается без единой строки
     скрипта. Здесь только надстройка: поиск, кнопки «развернуть/свернуть»,
     постоянные ссылки на вопрос и разметка для поисковиков. Если скрипт не
     выполнится, страница остаётся полностью рабочей. --}}
<script>
(function () {
    // Блоков вопросов на странице может быть несколько — по одному на раздел.
    // Сначала здесь брался только первый: счётчик показывал три вопроса вместо
    // пятнадцати, поиск искал в одном разделе, а остальные вопросы оставались
    // без якорей.
    const groups = [...document.querySelectorAll('.page-content .pc-faq')];
    if (!groups.length) return;

    const faq = groups[0];
    const items = groups.flatMap((g) => [...g.querySelectorAll('details.pc-faq__item')]);
    if (!items.length) return;

    // Идентификатор строится из текста вопроса: на такую ссылку можно
    // сослаться в переписке, и она переживёт перестановку вопросов местами.
    const translit = (t) => {
        const map = {'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'e','ж':'zh',
            'з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p',
            'р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'c','ч':'ch','ш':'sh',
            'щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya'};
        return t.toLowerCase().replace(/[а-яё]/g, (c) => (c in map ? map[c] : c))
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 60);
    };

    const seen = new Set();

    items.forEach((item, n) => {
        const summary = item.querySelector('summary');
        if (!summary) return;

        if (!item.id) {
            let id = 'v-' + (translit(summary.textContent.trim()) || (n + 1));
            while (seen.has(id)) id += '-' + (n + 1);
            item.id = id;
        }

        seen.add(item.id);

        // Адрес в строке браузера следует за раскрытым вопросом — его можно
        // просто скопировать, отдельная кнопка «поделиться» не нужна.
        item.addEventListener('toggle', () => {
            if (item.open) history.replaceState(null, '', '#' + item.id);
        });
    });

    // Заголовок раздела запоминаем ЗАРАНЕЕ: панель поиска встаёт перед первым
    // блоком и перестаёт быть его соседом — искать заголовок по соседству
    // после вставки уже нельзя, и у первого раздела он оставался висеть.
    const headings = new Map();

    groups.forEach((group) => {
        const prev = group.previousElementSibling;
        if (prev && /^H[23]$/.test(prev.tagName)) headings.set(group, prev);
    });

    const bar = document.createElement('div');
    bar.className = 'pc-faq-bar';
    bar.innerHTML =
        '<input type="search" class="pc-faq-bar__input" ' +
               'placeholder="Поиск по вопросам и ответам" aria-label="Поиск по вопросам">' +
        '<span class="pc-faq-bar__count" role="status"></span>' +
        '<button type="button" class="pc-faq-bar__btn" data-all="1">Развернуть всё</button>' +
        '<button type="button" class="pc-faq-bar__btn" data-all="0">Свернуть всё</button>';

    faq.parentNode.insertBefore(bar, faq);

    const empty = document.createElement('p');
    empty.className = 'pc-faq-empty';
    empty.hidden = true;
    empty.textContent = 'Ничего не нашлось. Попробуйте другое слово.';
    const last = groups[groups.length - 1];
    last.parentNode.insertBefore(empty, last.nextSibling);

    const input = bar.querySelector('.pc-faq-bar__input');
    const count = bar.querySelector('.pc-faq-bar__count');

    const setCount = (shown) => {
        count.textContent = shown === items.length
            ? 'Вопросов: ' + items.length
            : 'Найдено: ' + shown + ' из ' + items.length;
    };

    setCount(items.length);

    // Ищем и по вопросу, и по ответу: человек чаще помнит слово из ответа,
    // чем точную формулировку вопроса.
    let timer = null;

    const filter = () => {
        const q = input.value.trim().toLowerCase();
        let shown = 0;

        items.forEach((item) => {
            const hit = q === '' || item.textContent.toLowerCase().includes(q);
            item.hidden = !hit;

            if (hit) {
                shown++;
                // При поиске ответ сразу виден — иначе пришлось бы открывать
                // каждый найденный вопрос вручную.
                if (q !== '') item.open = true;
            }
        });

        if (q === '') items.forEach((item) => { item.open = false; });

        // Заголовок раздела без единого найденного вопроса только мешает.
        groups.forEach((group) => {
            const visible = [...group.querySelectorAll('details.pc-faq__item')]
                .some((item) => !item.hidden);

            group.hidden = !visible;

            const heading = headings.get(group);
            if (heading) heading.hidden = !visible;
        });

        empty.hidden = shown !== 0;
        setCount(shown);
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(filter, 150);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { input.value = ''; filter(); }
    });

    // Клавиша поиска, как в почтовых клиентах и трекерах задач.
    document.addEventListener('keydown', (e) => {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
        const tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || e.target.isContentEditable) return;
        e.preventDefault();
        input.focus();
    });

    bar.querySelectorAll('.pc-faq-bar__btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const open = btn.dataset.all === '1';
            items.forEach((item) => { if (!item.hidden) item.open = open; });
        });
    });

    const openFromHash = () => {
        const id = decodeURIComponent(location.hash.slice(1));
        if (!id) return;

        const target = items.find((item) => item.id === id);
        if (!target) return;

        target.open = true;
        target.scrollIntoView({ block: 'center' });
    };

    openFromHash();
    window.addEventListener('hashchange', openFromHash);

    // Разметка для поисковиков собирается из готового списка: редактору
    // ничего размечать не нужно — добавил вопрос, он попал и сюда.
    // Ключи схемы собираются из символа и слова, а не пишутся целиком.
    // Blade компилирует конструкции вида «собака плюс слово» ВЕЗДЕ, включая
    // содержимое тега script и комментарии, — литеральный ключ ломал
    // компиляцию всего шаблона (страница отдавала 500).
    const AT = String.fromCharCode(64);

    const answerOf = (item) => [...item.children]
        .filter((el) => el.tagName !== 'SUMMARY')
        .map((el) => el.textContent.trim())
        .join(' ')
        .trim();

    const questions = items.map((item) => {
        const q = {};
        q[AT + 'type'] = 'Question';
        q.name = ((item.querySelector('summary') || {}).textContent || '').trim();

        const a = {};
        a[AT + 'type'] = 'Answer';
        a.text = answerOf(item);
        q.acceptedAnswer = a;

        return q;
    }).filter((q) => q.name && q.acceptedAnswer.text);

    const data = {};
    data[AT + 'context'] = 'https://schema.org';
    data[AT + 'type'] = 'FAQPage';
    data.mainEntity = questions;

    if (questions.length) {
        const tag = document.createElement('script');
        tag.type = 'application/ld+json';
        tag.textContent = JSON.stringify(data);
        document.head.appendChild(tag);
    }
})();
</script>

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
{{-- Блоки содержимого вынесены в отдельный файл: он же подключается в
     визуальный редактор панели, поэтому редактор показывает блок так,
     как его увидит посетитель. --}}
<link rel="stylesheet" href="{{ asset('assets/css/content-blocks.css') }}">
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

</style>
@endpush
