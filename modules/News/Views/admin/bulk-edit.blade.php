@extends('layouts.admin')

@section('title', '{{ __('admin.news.bulk_edit') }}')

@section('content')
    {{-- ── Шапка страницы ── --}}
    <div class="admin-accent-bar mb-0"></div>
    <div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
                flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <span class="admin-icon-badge"><i class="fas fa-layer-group"></i></span>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.news.bulk_edit') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Правка заголовков и SEO-полей сразу у нескольких публикаций — выбрано записей: {{ count($news) }}.
                </p>
            </div>
        </div>

        <a href="{{ route('admin.news.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition shrink-0">
            <i class="fas fa-arrow-left"></i> К списку новостей
        </a>
    </div>

    @if ($errors->any())
        <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 px-4 py-3 mb-6 text-sm">
            <div class="flex items-start gap-2">
                <i class="fas fa-triangle-exclamation mt-0.5"></i>
                <div><b>{{ __('admin.common.check_form') }}</b> {{ $errors->first() }}</div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.news.bulk.update') }}" class="space-y-5">
        @csrf

        @foreach ($news as $item)
            <div class="admin-card p-5 space-y-4">
                <input type="hidden" name="fields[{{ $item->id }}][id]" value="{{ $item->id }}">

                {{-- Заголовок карточки записи --}}
                <div class="flex flex-wrap items-center gap-2 pb-3 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="font-semibold text-gray-900 dark:text-white min-w-0 truncate">{{ $item->title }}</h2>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        ID {{ $item->id }}
                    </span>
                    @if ($item->template === 'products')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                            <i class="fas fa-bag-shopping"></i> Товар
                        </span>
                    @endif
                    <a href="{{ route('admin.news.edit', $item->id) }}"
                       class="ml-auto inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                       title="{{ __('admin.news.full_form_title') }}">
                        <i class="fas fa-pen"></i> {{ __('admin.news.full_form') }}
                    </a>
                </div>

                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.news.title') }}</label>
                        <input type="text" name="fields[{{ $item->id }}][title]" value="{{ $item->title }}"
                               class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm dark:bg-gray-800 dark:text-gray-100
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Meta Title</label>
                        <input type="text" name="fields[{{ $item->id }}][meta_title]" value="{{ $item->meta_title }}"
                               class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm dark:bg-gray-800 dark:text-gray-100
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.news.keywords') }}</label>
                        <input type="text" name="fields[{{ $item->id }}][meta_keywords]" value="{{ $item->meta_keywords }}"
                               class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm dark:bg-gray-800 dark:text-gray-100
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Meta Description</label>
                        <textarea name="fields[{{ $item->id }}][meta_description]" rows="3"
                                  class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm dark:bg-gray-800 dark:text-gray-100
                                         focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">{{ $item->meta_description }}</textarea>
                    </div>

                    @if ($item->template === 'products')
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Цена (₽)</label>
                            <input type="number" step="0.01" name="fields[{{ $item->id }}][price]" value="{{ $item->price }}"
                                   class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm dark:bg-gray-800 dark:text-gray-100
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Остаток</label>
                            <input type="number" name="fields[{{ $item->id }}][stock]" value="{{ $item->stock }}"
                                   class="w-full border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm dark:bg-gray-800 dark:text-gray-100
                                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- ── Сохранение ── --}}
        <div class="admin-card p-5 flex flex-wrap items-center gap-3">
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.news.bulk_hint') }}
            </span>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('admin.news.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600
                          text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Отмена
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
                    <i class="fas fa-floppy-disk"></i> Сохранить изменения
                </button>
            </div>
        </div>
    </form>
@endsection
