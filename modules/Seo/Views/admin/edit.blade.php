@extends('layouts.admin')

@section('content')
    @php
        $base = rtrim((string) config('app.url'), '/');
        $viewUrl = !empty($item->canonical) ? $item->canonical : $base . '/' . ltrim((string) $item->slug, '/');
    @endphp

    <h1 class="text-2xl font-semibold mb-4">Редактирование SEO</h1>

    @if (session('status'))
        <div class="mb-4 p-3 rounded border border-emerald-300 bg-emerald-50 text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 rounded border border-red-300 bg-red-50 text-red-800">
            <strong>Проверьте поля:</strong> {{ $errors->first() }}
        </div>
    @endif

    {{-- 🔒/🔓 Lock banner --}}
    @if (!empty($item->locked))
        <div role="alert" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="border-l-4 border-amber-500 pl-3">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-600">@themeIcon('lock', 'text-lg')</span>
                        <span class="font-semibold">Поля заблокированы от перезаписи из источника</span>
                    </div>
                    <p class="text-sm opacity-90 mt-1">
                        Эта запись помечена как <b>locked</b>. Данные не будут перезаписаны ни синхронизатором,
                        ни формами источников (Новости/Страницы).
                    </p>
                    <form method="POST" action="{{ route('seo.pages.unlock', $item->id) }}" class="mt-3">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md
                               bg-amber-600 hover:bg-amber-700 text-black
                               shadow-sm ring-1 ring-inset ring-amber-700/10
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-600">
                            @themeIcon('unlock')
                            Разблокировать
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div role="alert" class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-900 p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="border-l-4 border-emerald-500 pl-3">
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-600">@themeIcon('unlock', 'text-lg')</span>
                        <span class="font-semibold">Поля сейчас разблокированы</span>
                    </div>
                    <p class="text-sm opacity-90 mt-1">
                        Разрешена запись из источников (например, из модуля Новости) и синхронизация.
                        Чтобы закрепить вручную — включите блокировку.
                    </p>
                    <form method="POST" action="{{ route('seo.pages.lock', $item->id) }}" class="mt-3">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md
                               bg-emerald-600 hover:bg-emerald-700 text-black
                               shadow-sm ring-1 ring-inset ring-emerald-700/10
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-600">
                            @themeIcon('lock')
                            Заблокировать
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Левая зона: форма редактирования --}}
        <div class="lg:col-span-2 space-y-5">
            <form method="post" action="{{ route('seo.pages.update', $item->id) }}">
                @csrf
                @method('PUT')

                {{-- Slug --}}
                <div>
                    <label class="block text-sm font-medium">Slug
                        <span class="text-xs text-gray-500">(путь или полный URL)</span>
                    </label>
                    <input name="slug" value="{{ old('slug', $item->slug) }}" class="mt-1 border p-2 rounded w-full"
                        maxlength="1024" placeholder="/news/primer-1 или https://site.ru/news/primer-1">
                    <p class="text-xs text-gray-500 mt-1">
                        Менять slug стоит только при необходимости — это влияет на URL.
                    </p>
                    @error('slug')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Title / H1 --}}
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Title</label>
                        <input name="title" value="{{ old('title', $item->title) }}"
                            class="mt-1 border p-2 rounded w-full js-count" data-limit="60" maxlength="255"
                            placeholder="До ~60 символов">
                        <div class="text-xs text-gray-500 mt-1">
                            Рекомендуем до 60 символов. <span class="js-count-out"></span>
                        </div>
                        @error('title')
                            <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">H1</label>
                        <input name="h1" value="{{ old('h1', $item->h1) }}" class="mt-1 border p-2 rounded w-full"
                            maxlength="255" placeholder="Основной заголовок на странице">
                        @error('h1')
                            <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium">Description</label>
                    <textarea name="description" rows="2" class="mt-1 border p-2 rounded w-full js-count" data-limit="160"
                        maxlength="255" placeholder="Краткое описание для сниппета (до 160 символов)">{{ old('description', $item->description) }}</textarea>
                    <div class="text-xs text-gray-500 mt-1">
                        Лучше до 160 символов. <span class="js-count-out"></span>
                    </div>
                    @error('description')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Keywords --}}
                <div>
                    <label class="block text-sm font-medium">Keywords
                        <span class="text-xs text-gray-500">(через запятую)</span>
                    </label>
                    <input name="keywords" value="{{ old('keywords', $item->keywords) }}"
                        class="mt-1 border p-2 rounded w-full js-count" data-limit="255" maxlength="255"
                        placeholder="новости, мероприятия, экология">
                    <div class="text-xs text-gray-500 mt-1">
                        Не обязательно. <span class="js-count-out"></span>
                    </div>
                    @error('keywords')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Canonical --}}
                <div>
                    <label class="block text-sm font-medium">Canonical
                        <span class="text-xs text-gray-500">(можно относительный — станет абсолютным)</span>
                    </label>
                    <input name="canonical" value="{{ old('canonical', $item->canonical) }}"
                        class="mt-1 border p-2 rounded w-full" maxlength="1024"
                        placeholder="/news/primer-1 или https://site.ru/news/primer-1">
                    <div class="text-xs text-gray-500 mt-1">
                        Если оставить пустым — каноникал не будет задан.
                    </div>
                    @error('canonical')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Robots --}}
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <input type="hidden" name="robots_index" value="0">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="robots_index" value="1" class="mr-2"
                                {{ old('robots_index', $item->robots_index) ? 'checked' : '' }}>
                            index
                        </label>
                        <p class="text-xs text-gray-500">Разрешить индексирование страницы.</p>
                        @error('robots_index')
                            <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <input type="hidden" name="robots_follow" value="0">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="robots_follow" value="1" class="mr-2"
                                {{ old('robots_follow', $item->robots_follow) ? 'checked' : '' }}>
                            follow
                        </label>
                        <p class="text-xs text-gray-500">Разрешить переход по ссылкам со страницы.</p>
                        @error('robots_follow')
                            <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="text-xs text-gray-500 -mt-2">
                    Итоговая директива будет: <code>
                        {{ old('robots_index', $item->robots_index) ? 'index' : 'noindex' }},
                        {{ old('robots_follow', $item->robots_follow) ? 'follow' : 'nofollow' }}
                    </code>
                </div>

                {{-- OG / Twitter --}}
                <div class="border rounded p-3 space-y-2">
                    <div class="text-sm font-semibold">OG / Twitter</div>
                    <input name="og_title" value="{{ old('og_title', $item->og['og:title'] ?? '') }}"
                        class="w-full border p-2 rounded" maxlength="255" placeholder="og:title">
                    <input name="og_description" value="{{ old('og_description', $item->og['og:description'] ?? '') }}"
                        class="w-full border p-2 rounded" maxlength="512" placeholder="og:description">
                    <input name="og_image" value="{{ old('og_image', $item->og['og:image'] ?? '') }}"
                        class="w-full border p-2 rounded" maxlength="1024" placeholder="og:image (URL)">
                    <input name="twitter_card" value="{{ old('twitter_card', $item->og['twitter:card'] ?? '') }}"
                        class="w-full border p-2 rounded" maxlength="50"
                        placeholder="twitter:card (summary / summary_large_image)">
                    <input name="twitter_title" value="{{ old('twitter_title', $item->og['twitter:title'] ?? '') }}"
                        class="w-full border p-2 rounded" maxlength="255" placeholder="twitter:title">
                    <input name="twitter_description"
                        value="{{ old('twitter_description', $item->og['twitter:description'] ?? '') }}"
                        class="w-full border p-2 rounded" maxlength="512" placeholder="twitter:description">
                    <input name="twitter_image" value="{{ old('twitter_image', $item->og['twitter:image'] ?? '') }}"
                        class="w-full border p-2 rounded" maxlength="1024" placeholder="twitter:image (URL)">
                    <p class="text-xs text-gray-500">Незаполненные поля не перезапишут данные при сохранении.</p>
                </div>

                {{-- JSON-LD --}}
                <div>
                    <label class="block text-sm font-medium">JSON-LD</label>
                    <textarea name="jsonld_raw" rows="8" class="mt-1 w-full border p-2 rounded font-mono"
                        placeholder='{"@context":"https://schema.org","@type":"Article",...}'>{{ old('jsonld_raw', isset($item->jsonld) ? json_encode($item->jsonld, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '') }}</textarea>
                    <div class="flex items-center justify-between mt-1">
                        <div class="text-xs text-gray-500">Сохраняем только валидный JSON.</div>
                        <button type="button"
                            class="px-2 py-1 text-xs border rounded js-json-pretty">Форматировать</button>
                    </div>
                    @error('jsonld_raw')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Кнопка Сохранить --}}
                <div class="flex flex-wrap items-center gap-3">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded">Сохранить</button>
                    <a href="{{ $viewUrl }}" target="_blank" rel="noopener"
                        class="px-4 py-2 border rounded hover:bg-gray-50">Открыть страницу</a>
                    <button type="button" class="px-4 py-2 border rounded hover:bg-gray-50"
                        title="Скопировать URL страницы" data-url="{{ $viewUrl }}"
                        onclick="navigator.clipboard?.writeText(this.dataset.url).then(()=>{ this.textContent='Скопировано'; setTimeout(()=>this.textContent='Копировать URL',1500); });">
                        Копировать URL
                    </button>
                </div>
            </form>

            {{-- ВНЕ основной формы: отдельные действия, чтобы не было вложенных форм --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- Пересинхронизация одной записи --}}
                <form method="post" action="{{ route('seo.pages.refresh', $item->id) }}"
                    onsubmit="return confirm('Пересинхронизировать страницу из источника без перезаписи ваших ручных правок?');">
                    @csrf
                    <button class="px-4 py-2 border rounded hover:bg-gray-50">Пересинхронизировать</button>
                </form>

                {{-- Удаление --}}
                <form action="{{ route('seo.pages.destroy', $item->id) }}" method="post"
                    onsubmit="return confirm('Удалить эту SEO-запись? Если включён автосинк из источников, она может появиться снова.');">
                    @csrf @method('DELETE')
                    <button class="px-4 py-2 bg-red-600 text-white rounded">Удалить</button>
                </form>

                <a href="{{ route('seo.pages.index') }}" class="px-4 py-2 bg-gray-200 rounded">К списку</a>
            </div>
        </div>

        {{-- Правая колонка: подсказки и мета-инфо --}}
        <aside class="space-y-3">
            <div class="p-3 rounded border bg-white">
                <div class="font-semibold mb-1">Сведения</div>
                <div class="text-sm space-y-1">
                    <div><span class="text-gray-500">ID:</span> {{ $item->id }}</div>
                    @if ($item->updated_at)
                        <div><span class="text-gray-500">Обновлено:</span> {{ $item->updated_at->format('d.m.Y H:i') }}
                        </div>
                    @endif
                    @if (!empty($item->source_type))
                        <div><span class="text-gray-500">Источник:</span> {{ $item->source_type }}@if ($item->source_id)
                                #{{ $item->source_id }}
                            @endif
                        </div>
                    @endif
                    @php $manualCount = is_array($item->manual_fields ?? null) ? count($item->manual_fields) : 0; @endphp
                    <div>
                        <span class="text-gray-500">Ручные поля:</span>
                        {{ $manualCount > 0 ? $manualCount : '—' }}
                    </div>
                    @if (!empty($item->locked))
                        <div><span class="text-gray-500">Статус:</span> locked</div>
                    @endif
                </div>
            </div>

            <div class="p-3 rounded border bg-white">
                <div class="font-semibold mb-1">Подсказки</div>
                <ul class="list-disc pl-5 text-sm space-y-1 text-gray-700">
                    <li><strong>Title</strong> — заголовок сниппета (до ~60).</li>
                    <li><strong>H1</strong> — заголовок на странице.</li>
                    <li><strong>Description</strong> — краткое описание (до ~160).</li>
                    <li><strong>Canonical</strong> — укажите, если есть дубликаты.</li>
                    <li><strong>index/follow</strong> — индексация страницы и ссылок.</li>
                    <li>Кнопка <strong>Пересинхронизировать</strong> подтянет данные из источника (Новости/Страницы) не
                        перезаписывая ваши ручные поля.</li>
                </ul>
            </div>

            @if (Route::has('seo.sitemaps.rebuild'))
                <form method="post" action="{{ route('seo.sitemaps.rebuild') }}" class="p-3 rounded border bg-white">
                    @csrf
                    <div class="font-semibold mb-2">Sitemap</div>
                    <button
                        class="px-3 py-2 rounded border border-sky-700 text-sky-700 bg-white hover:bg-sky-50 transition w-full">
                        Пересобрать sitemap
                    </button>
                    <div class="text-xs text-gray-500 mt-2">
                        Если очередь не настроена, пересборка выполнится синхронно (зависит от конфигурации).
                    </div>
                </form>
            @endif
        </aside>
    </div>

    {{-- Мини-скрипты UX --}}
    <script>
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

        document.querySelectorAll('.js-json-pretty').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const wrap = this.closest('div').previousElementSibling ? this.closest('div')
                    .previousElementSibling : null;
                const ta = wrap && wrap.tagName === 'TEXTAREA' ? wrap : document.querySelector(
                    'textarea[name="jsonld_raw"]');
                if (!ta) return;
                try {
                    const parsed = JSON.parse(ta.value || '{}');
                    ta.value = JSON.stringify(parsed, null, 2);
                } catch (e) {
                    alert('Некорректный JSON');
                }
            });
        });
    </script>
@endsection
