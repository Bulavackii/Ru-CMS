@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-semibold mb-4">Новая запись SEO</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded border border-red-300 bg-red-50 text-red-800">
            <strong>Проверьте поля:</strong> {{ $errors->first() }}
        </div>
    @endif

    <form method="post" action="{{ route('seo.pages.store') }}" class="grid lg:grid-cols-3 gap-6">
        @csrf

        {{-- Левая колонка: форма --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Slug --}}
            <div>
                <label class="block text-sm font-medium">Slug
                    <span class="text-xs text-gray-500">(путь или полный URL)</span>
                </label>
                <input name="slug" value="{{ old('slug') }}" class="mt-1 border p-2 rounded w-full" maxlength="1024"
                    required placeholder="/news/primer-1 или https://site.ru/news/primer-1">
                <p class="text-xs text-gray-500 mt-1">
                    Относительный путь автоматически нормализуется (начинается со <code>/</code>, без хвостового
                    <code>/</code>).
                </p>
                @error('slug')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Title / H1 --}}
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium">Title</label>
                    <input name="title" value="{{ old('title') }}" class="mt-1 border p-2 rounded w-full js-count"
                        data-limit="60" maxlength="255" placeholder="До ~60 символов">
                    <div class="text-xs text-gray-500 mt-1">
                        Title для вкладки браузера и сниппета. Рекомендуем до 60 символов.
                        <span class="js-count-out"></span>
                    </div>
                    @error('title')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">H1</label>
                    <input name="h1" value="{{ old('h1') }}" class="mt-1 border p-2 rounded w-full" maxlength="255"
                        placeholder="Основной заголовок на странице">
                    @error('h1')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium">Description</label>
                <textarea name="description" rows="2" class="mt-1 border p-2 rounded w-full js-count" data-limit="160"
                    maxlength="255" placeholder="Краткое описание для сниппета (до 160 символов)">{{ old('description') }}</textarea>
                <div class="text-xs text-gray-500 mt-1">
                    Лучше до 160 символов. Лишнее будет обрезано поисковиком.
                    <span class="js-count-out"></span>
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
                <input name="keywords" value="{{ old('keywords') }}" class="mt-1 border p-2 rounded w-full js-count"
                    data-limit="255" maxlength="255" placeholder="новости, мероприятия, экология">
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
                <input name="canonical" value="{{ old('canonical') }}" class="mt-1 border p-2 rounded w-full"
                    maxlength="1024" placeholder="/news/primer-1 или https://site.ru/news/primer-1">
                @error('canonical')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Robots --}}
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <input type="hidden" name="robots_index" value="0">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="robots_index" value="1" class="mr-2 js-robots-index"
                            {{ old('robots_index', true) ? 'checked' : '' }}>
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
                        <input type="checkbox" name="robots_follow" value="1" class="mr-2 js-robots-follow"
                            {{ old('robots_follow', true) ? 'checked' : '' }}>
                        follow
                    </label>
                    <p class="text-xs text-gray-500">Разрешить переход по ссылкам со страницы.</p>
                    @error('robots_follow')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="text-xs text-gray-500 -mt-2">
                Итоговая директива: <code id="robotsPreview">
                    {{ old('robots_index', true) ? 'index' : 'noindex' }},
                    {{ old('robots_follow', true) ? 'follow' : 'nofollow' }}
                </code>
            </div>

            {{-- OG / Twitter --}}
            <div class="border rounded p-3 space-y-2">
                <div class="text-sm font-semibold">OG / Twitter</div>
                <input name="og_title" value="{{ old('og_title') }}" class="w-full border p-2 rounded" maxlength="255"
                    placeholder="og:title">
                <input name="og_description" value="{{ old('og_description') }}" class="w-full border p-2 rounded"
                    maxlength="512" placeholder="og:description">
                <input name="og_image" value="{{ old('og_image') }}" class="w-full border p-2 rounded" maxlength="1024"
                    placeholder="og:image (URL)">
                <input name="twitter_card" value="{{ old('twitter_card') }}" class="w-full border p-2 rounded"
                    maxlength="50" placeholder="twitter:card (summary / summary_large_image)">
                <input name="twitter_title" value="{{ old('twitter_title') }}" class="w-full border p-2 rounded"
                    maxlength="255" placeholder="twitter:title">
                <input name="twitter_description" value="{{ old('twitter_description') }}"
                    class="w-full border p-2 rounded" maxlength="512" placeholder="twitter:description">
                <input name="twitter_image" value="{{ old('twitter_image') }}" class="w-full border p-2 rounded"
                    maxlength="1024" placeholder="twitter:image (URL)">
                <p class="text-xs text-gray-500">Заполняйте по необходимости — незаполненные поля не попадут в базу.</p>
            </div>

            {{-- JSON-LD --}}
            <div class="border rounded p-3">
                <label class="block text-sm font-medium mb-2">JSON-LD</label>
                <textarea name="jsonld_raw" rows="8" class="w-full border p-2 rounded font-mono"
                    placeholder='{"@context":"https://schema.org","@type":"Article",...}'>{{ old('jsonld_raw') }}</textarea>
                <div class="flex items-center justify-between mt-2">
                    <div class="text-xs text-gray-500">Мы сохраним JSON только если он валидный.</div>
                    <button type="button" class="px-2 py-1 text-xs border rounded js-json-pretty">Форматировать</button>
                </div>
                @error('jsonld_raw')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button class="px-4 py-2 bg-blue-600 text-white rounded">Создать</button>
                <a href="{{ route('seo.pages.index') }}" class="px-4 py-2 bg-gray-200 rounded">Отмена</a>
            </div>
        </div>

        {{-- Правая колонка: подсказки --}}
        <aside class="space-y-3">
            <div class="p-3 rounded border bg-white">
                <div class="font-semibold mb-1">Подсказки</div>
                <ul class="list-disc pl-5 text-sm space-y-1 text-gray-700">
                    <li><strong>Title</strong> — заголовок сниппета (до ~60 символов).</li>
                    <li><strong>H1</strong> — заголовок на странице (может совпадать с Title).</li>
                    <li><strong>Description</strong> — краткое описание (до ~160 символов).</li>
                    <li><strong>Canonical</strong> — канонический адрес оригинала.</li>
                    <li><strong>index/follow</strong> — управление индексацией и ссылками.</li>
                </ul>
            </div>
        </aside>
    </form>

    {{-- Мини-скрипты UX --}}
    <script>
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

        // Превью директивы robots
        const idx = document.querySelector('.js-robots-index');
        const fol = document.querySelector('.js-robots-follow');
        const prev = document.getElementById('robotsPreview');
        const upd = () => {
            if (prev) prev.textContent = (idx?.checked ? 'index' : 'noindex') + ', ' + (fol?.checked ? 'follow' :
                'nofollow');
        };
        idx?.addEventListener('change', upd);
        fol?.addEventListener('change', upd);
        upd();

        // Красивый JSON
        document.querySelectorAll('.js-json-pretty').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const ta = this.closest('.border').querySelector('textarea[name="jsonld_raw"]');
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
