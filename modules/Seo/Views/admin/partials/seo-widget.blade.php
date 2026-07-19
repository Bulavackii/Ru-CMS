@php
    use Illuminate\Support\Str;
    $wid = 'seoW_' . Str::random(6); // уникальный префикс для id в инклюдах
    $base = rtrim((string) config('app.url'), '/');
    $slugVal = old('seo.slug', $seo->slug ?? ($slug ?? ''));
    $viewUrl = $seo->canonical ?? ($slugVal ? $base . '/' . ltrim($slugVal, '/') : null);
@endphp

<div class="p-4 bg-white rounded shadow space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-semibold">SEO</h3>
            <div class="text-xs text-gray-500">Для: {{ $entity_type }} #{{ $entity_id ?? '—' }}</div>
        </div>

        @if ($viewUrl)
            <div class="flex gap-2">
                <a href="{{ $viewUrl }}" target="_blank" rel="noopener"
                    class="text-xs px-2 py-1 border rounded hover:bg-gray-50">Открыть</a>
                <button type="button" class="text-xs px-2 py-1 border rounded hover:bg-gray-50"
                    onclick="navigator.clipboard?.writeText('{{ $viewUrl }}')">Копировать URL</button>
            </div>
        @endif
    </div>

    {{-- связь с сущностью --}}
    <input type="hidden" name="seo[entity_type]" value="{{ $entity_type }}">
    <input type="hidden" name="seo[entity_id]" value="{{ $entity_id }}">

    {{-- Slug + Canonical --}}
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm">Slug
                <span class="text-gray-400 text-xs">(путь или полный URL)</span>
            </label>
            <input id="{{ $wid }}_slug" name="seo[slug]" value="{{ $slugVal }}"
                class="w-full border p-2 rounded" maxlength="1024" required
                placeholder="/news/primer-1 или https://site.ru/news/primer-1">
            @error('seo.slug')
                <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
            @enderror
            <div class="text-xs text-gray-500 mt-1">
                Относительный путь будет нормализован (начинается с <code>/</code>, без хвостового <code>/</code>).
            </div>
        </div>

        <div>
            <label class="block text-sm">Canonical
                <span class="text-gray-400 text-xs">(можно относительный — станет абсолютным)</span>
            </label>
            <input name="seo[canonical]" value="{{ old('seo.canonical', $seo->canonical ?? '') }}"
                class="w-full border p-2 rounded" maxlength="1024"
                placeholder="/news/primer-1 или https://site.ru/news/primer-1">
            @error('seo.canonical')
                <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Title / H1 --}}
    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm">Title</label>
            <input id="{{ $wid }}_title" name="seo[title]" value="{{ old('seo.title', $seo->title ?? '') }}"
                class="w-full border p-2 rounded js-count" data-limit="60" maxlength="255"
                placeholder="До ~60 символов">
            <div class="text-xs text-gray-500 mt-1">
                Для сниппета в поиске. <span class="js-count-out"></span>
            </div>
            @error('seo.title')
                <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="block text-sm">H1</label>
            <input name="seo[h1]" value="{{ old('seo.h1', $seo->h1 ?? '') }}" class="w-full border p-2 rounded"
                maxlength="255" placeholder="Основной заголовок на странице">
            @error('seo.h1')
                <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Description --}}
    <div>
        <label class="block text-sm">Description</label>
        <textarea name="seo[description]" class="w-full border p-2 rounded js-count" data-limit="160" maxlength="255"
            rows="2" placeholder="Краткое описание для сниппета (до 160 символов)">{{ old('seo.description', $seo->description ?? '') }}</textarea>
        <div class="text-xs text-gray-500 mt-1">
            Лучше до 160 символов. <span class="js-count-out"></span>
        </div>
        @error('seo.description')
            <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
        @enderror
    </div>

    {{-- Keywords --}}
    <div>
        <label class="block text-sm">Keywords</label>
        <input name="seo[keywords]" value="{{ old('seo.keywords', $seo->keywords ?? '') }}"
            class="w-full border p-2 rounded" maxlength="255"
            placeholder="ключевые фразы через запятую: новости, мероприятия, экология">
        @error('seo.keywords')
            <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
        @enderror
        <div class="text-xs text-gray-500">Через запятую. Необязательно.</div>
    </div>

    {{-- Robots --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <input type="hidden" name="seo[robots_index]" value="0">
            <label class="inline-flex items-center">
                <input type="checkbox" name="seo[robots_index]" value="1"
                    {{ old('seo.robots_index', $seo->robots_index ?? true) ? 'checked' : '' }} class="mr-2"> index
            </label>
            @error('seo.robots_index')
                <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
            @enderror
            <div class="text-xs text-gray-500">Разрешить индексирование страницы.</div>
        </div>

        <div>
            <input type="hidden" name="seo[robots_follow]" value="0">
            <label class="inline-flex items-center">
                <input type="checkbox" name="seo[robots_follow]" value="1"
                    {{ old('seo.robots_follow', $seo->robots_follow ?? true) ? 'checked' : '' }} class="mr-2"> follow
            </label>
            @error('seo.robots_follow')
                <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
            @enderror
            <div class="text-xs text-gray-500">Разрешить переход по ссылкам со страницы.</div>
        </div>
    </div>

    {{-- OG / Twitter --}}
    <div class="border rounded p-3 space-y-2">
        <div class="text-sm font-semibold">OG / Twitter</div>
        <input name="seo[og_title]" value="{{ old('seo.og_title', $seo->og['og:title'] ?? '') }}"
            class="w-full border p-2 rounded" maxlength="255" placeholder="og:title">
        <input name="seo[og_description]" value="{{ old('seo.og_description', $seo->og['og:description'] ?? '') }}"
            class="w-full border p-2 rounded" maxlength="512" placeholder="og:description">
        <input name="seo[og_image]" value="{{ old('seo.og_image', $seo->og['og:image'] ?? '') }}"
            class="w-full border p-2 rounded" maxlength="1024" placeholder="og:image (URL)">
        <input name="seo[twitter_card]" value="{{ old('seo.twitter_card', $seo->og['twitter:card'] ?? '') }}"
            class="w-full border p-2 rounded" maxlength="50"
            placeholder="twitter:card (summary / summary_large_image)">
        <input name="seo[twitter_title]" value="{{ old('seo.twitter_title', $seo->og['twitter:title'] ?? '') }}"
            class="w-full border p-2 rounded" maxlength="255" placeholder="twitter:title">
        <input name="seo[twitter_description]"
            value="{{ old('seo.twitter_description', $seo->og['twitter:description'] ?? '') }}"
            class="w-full border p-2 rounded" maxlength="512" placeholder="twitter:description">
        <input name="seo[twitter_image]" value="{{ old('seo.twitter_image', $seo->og['twitter:image'] ?? '') }}"
            class="w-full border p-2 rounded" maxlength="1024" placeholder="twitter:image (URL)">
        <p class="text-xs text-gray-500">Незаполненные поля не будут сохранены/перезаписаны.</p>
    </div>

    {{-- JSON-LD --}}
    <div>
        <label class="block text-sm">JSON-LD</label>
        <textarea id="{{ $wid }}_json" name="seo[jsonld_raw]" rows="6"
            class="w-full border p-2 rounded font-mono" placeholder='{"@context":"https://schema.org","@type":"Article",...}'>{{ old('seo.jsonld_raw', isset($seo->jsonld) ? json_encode($seo->jsonld, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '') }}</textarea>
        @error('seo.jsonld_raw')
            <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
        @enderror
        <div class="flex items-center justify-between mt-1">
            <div class="text-xs text-gray-500">Вставьте валидный JSON — мы сохраним его, только если парсинг успешен.
            </div>
            <button type="button" class="px-2 py-1 text-xs border rounded hover:bg-gray-50"
                onclick="(function(){try{const ta=document.getElementById('{{ $wid }}_json'); if(!ta) return; const obj=JSON.parse(ta.value||'{}'); ta.value=JSON.stringify(obj,null,2);}catch(e){alert('Некорректный JSON');}})()">
                Форматировать
            </button>
        </div>
    </div>
</div>

{{-- Мини-скрипты UX (изолированы по id-префиксу) --}}
<script>
    (function() {
        // Счётчики символов
        document.querySelectorAll('.js-count').forEach(function(el) {
            const out = el.parentElement.querySelector('.js-count-out');
            const lim = parseInt(el.dataset.limit || '0', 10);
            const apply = () => {
                const len = (el.value || '').length;
                if (!out) return;
                out.textContent = lim ? ` • ${len}/${lim}` : ` • ${len}`;
                out.className = 'js-count-out ' + (lim && len > lim ? 'text-red-600' : 'text-gray-400');
            };
            el.addEventListener('input', apply);
            apply();
        });

        // Автоподстановка slug из Title (только если slug пустой и не URL)
        const slug = document.getElementById('{{ $wid }}_slug');
        const title = document.getElementById('{{ $wid }}_title');

        function slugify(t) {
            if (!t) return '';
            // упрощённая транслитерация + slug
            return '/' + t.toLowerCase()
                .replace(/[а-яё]/g, c => ({
                    'а': 'a',
                    'б': 'b',
                    'в': 'v',
                    'г': 'g',
                    'д': 'd',
                    'е': 'e',
                    'ё': 'e',
                    'ж': 'zh',
                    'з': 'z',
                    'и': 'i',
                    'й': 'y',
                    'к': 'k',
                    'л': 'l',
                    'м': 'm',
                    'н': 'n',
                    'о': 'o',
                    'п': 'p',
                    'р': 'r',
                    'с': 's',
                    'т': 't',
                    'у': 'u',
                    'ф': 'f',
                    'х': 'h',
                    'ц': 'c',
                    'ч': 'ch',
                    'ш': 'sh',
                    'щ': 'sch',
                    'ъ': '',
                    'ы': 'y',
                    'ь': '',
                    'э': 'e',
                    'ю': 'yu',
                    'я': 'ya'
                } [c]) || c)
                .replace(/[^\w\-\/]+/g, '-')
                .replace(/--+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/\/+/g, '/')
                .replace(/^(?!\/)/, '/')
                .replace(/\/$/, '');
        }
        if (slug && title) {
            title.addEventListener('blur', function() {
                const v = (slug.value || '').trim();
                const isUrl = /^https?:\/\//i.test(v);
                if (!v && !isUrl) slug.value = slugify((title.value || '').trim());
            });
        }
    })();
</script>
