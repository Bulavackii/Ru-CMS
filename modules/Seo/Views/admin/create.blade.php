@extends('layouts.admin')

@section('title', __('admin.seo.new_seo'))

@section('content')
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-plus"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.seo.new_url') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.seo.new_url_hint') }}
                </p>
            </div>
        </div>

        <a href="{{ route('seo.pages.index') }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
            <i class="fas fa-arrow-left"></i> {{ __('admin.seo.back') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="admin-card border-l-4 border-red-500 p-4 mb-5">
            <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('seo.pages.store') }}" class="grid lg:grid-cols-3 gap-6">
        @csrf

        {{-- Левая колонка: форма --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Slug --}}
            <div>
                <label class="block text-sm font-medium">Slug
                    <span class="text-xs text-gray-500">{{ __('admin.seo.path_or_url') }}</span>
                </label>
                <input name="slug" value="{{ old('slug') }}" class="mt-1 border p-2 rounded w-full" maxlength="1024"
                    required placeholder="{{ __('admin.seo.url_ph') }}">
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('admin.seo.path_note') }} <code>/</code>{{ __('admin.seo.no_trailing') }}
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
                        data-limit="60" maxlength="255" placeholder="{{ __('admin.seo.title_ph') }}">
                    <div class="text-xs text-gray-500 mt-1">
                        {{ __('admin.seo.title_hint') }}
                        <span class="js-count-out"></span>
                    </div>
                    @error('title')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">H1</label>
                    <input name="h1" value="{{ old('h1') }}" class="mt-1 border p-2 rounded w-full" maxlength="255"
                        placeholder="{{ __('admin.seo.h1_ph') }}">
                    @error('h1')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium">Description</label>
                <textarea name="description" rows="2" class="mt-1 border p-2 rounded w-full js-count" data-limit="160"
                    maxlength="255" placeholder="{{ __('admin.seo.desc_ph') }}">{{ old('description') }}</textarea>
                <div class="text-xs text-gray-500 mt-1">
                    {{ __('admin.seo.desc_hint') }}
                    <span class="js-count-out"></span>
                </div>
                @error('description')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Keywords --}}
            <div>
                <label class="block text-sm font-medium">Keywords
                    <span class="text-xs text-gray-500">{{ __('admin.seo.comma_separated') }}</span>
                </label>
                <input name="keywords" value="{{ old('keywords') }}" class="mt-1 border p-2 rounded w-full js-count"
                    data-limit="255" maxlength="255" placeholder="{{ __('admin.seo.keywords_ph') }}">
                <div class="text-xs text-gray-500 mt-1">
                    {{ __('admin.seo.optional') }} <span class="js-count-out"></span>
                </div>
                @error('keywords')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Canonical --}}
            <div>
                <label class="block text-sm font-medium">Canonical
                    <span class="text-xs text-gray-500">{{ __('admin.seo.can_be_relative') }}</span>
                </label>
                <input name="canonical" value="{{ old('canonical') }}" class="mt-1 border p-2 rounded w-full"
                    maxlength="1024" placeholder="{{ __('admin.seo.url_ph') }}">
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
                    <p class="text-xs text-gray-500">{{ __('admin.seo.allow_indexing') }}</p>
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
                    <p class="text-xs text-gray-500">{{ __('admin.seo.allow_follow') }}</p>
                    @error('robots_follow')
                        <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="text-xs text-gray-500 -mt-2">
                {{ __('admin.seo.directive') }} <code id="robotsPreview">
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
                <p class="text-xs text-gray-500">{{ __('admin.seo.fill_as_needed') }}</p>
            </div>

            {{-- JSON-LD --}}
            <div class="border rounded p-3">
                <label class="block text-sm font-medium mb-2">JSON-LD</label>
                <textarea name="jsonld_raw" rows="8" class="w-full border p-2 rounded font-mono"
                    placeholder='{"@@context":"https://schema.org","@@type":"Article",...}'>{{ old('jsonld_raw') }}</textarea>
                <div class="flex items-center justify-between mt-2">
                    <div class="text-xs text-gray-500">{{ __('admin.seo.json_valid') }}</div>
                    <button type="button" class="px-2 py-1 text-xs border rounded js-json-pretty">{{ __('admin.seo.format') }}</button>
                </div>
                @error('jsonld_raw')
                    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">{{ __('admin.admin.create') }}</button>
                <a href="{{ route('seo.pages.index') }}" class="inline-flex items-center gap-2 border border-gray-300 text-gray-700 hover:bg-gray-100 px-4 py-2 text-sm font-semibold transition">{{ __('admin.admin.cancel') }}</a>
            </div>
        </div>

        {{-- Правая колонка: подсказки --}}
        <aside class="space-y-3">
            <div class="p-3 rounded border bg-white">
                <div class="font-semibold mb-1">{{ __('admin.seo.hints') }}</div>
                <ul class="list-disc pl-5 text-sm space-y-1 text-gray-700">
                    <li><strong>Title</strong> {{ __('admin.seo.hint_title') }}</li>
                    <li><strong>H1</strong> {{ __('admin.seo.hint_h1') }}</li>
                    <li><strong>Description</strong> {{ __('admin.seo.hint_desc') }}</li>
                    <li><strong>Canonical</strong> {{ __('admin.seo.hint_canonical') }}</li>
                    <li><strong>index/follow</strong> {{ __('admin.seo.hint_robots') }}</li>
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
                    alert(@js(__('admin.seo.bad_json')));
                }
            });
        });
    </script>
@endsection
