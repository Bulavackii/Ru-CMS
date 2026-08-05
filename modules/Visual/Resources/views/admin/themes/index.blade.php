@extends('layouts.admin')
@section('title','Темы')

@section('content')
@php
    $iconModeLabels = [
        'lucide' => 'Lucide', 'fa' => 'Font Awesome', 'bootstrap' => 'Bootstrap',
        'remix' => 'Remix', 'tabler' => 'Tabler', 'svg' => 'Свой набор SVG',
    ];
@endphp

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-palette"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Темы оформления</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Цвета, шрифт и иконки сайта. Активная тема применяется сразу ко всем страницам.
            </p>
        </div>
    </div>

    <a href="{{ route('admin.visual.themes.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition flex-shrink-0">
        <i class="fas fa-plus"></i> Создать тему
    </a>
</div>

@includeIf('layouts.partials.flash')

@if($themes->isEmpty())
    {{-- ── Пустое состояние ── --}}
    <div class="admin-card p-10 text-center">
        <span class="admin-icon-badge mx-auto mb-4"><i class="fas fa-palette"></i></span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Тем пока нет</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-2xl mx-auto mb-5">
            Тема задаёт оформление всего сайта: цвета фона, текста и акцентов, скругления,
            шрифт и набор иконок. Пять готовых тем создаются при установке — если раздел
            пуст, их добавит команда <span class="font-mono">php artisan themes:seed-default</span>.
        </p>
        <a href="{{ route('admin.visual.themes.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-plus"></i> Создать первую тему
        </a>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($themes as $theme)
            @php
                $tokens   = $theme->tokens ?? [];
                $config   = $theme->config ?? [];
                $colors   = (array) data_get($tokens, 'colors', []);
                $radius   = data_get($tokens, 'radius.md', '12px');
                $iconMode = data_get($config, 'icon_mode', 'lucide');
                $fontName = data_get($config, 'font_name') ?: 'шрифт по умолчанию';
                $note     = data_get($config, 'note');
                $swatches = [
                    'primary' => 'Акцент', 'accent' => 'Дополнительный',
                    'bg' => 'Фон', 'text' => 'Текст',
                    'header' => 'Шапка', 'footer' => 'Подвал',
                ];
            @endphp

            <div class="admin-card p-0 overflow-hidden {{ $theme->is_default ? 'theme-card--active' : '' }}">
                {{-- Мини-макет страницы: тему видно до применения --}}
                <div class="theme-preview" style="background: {{ data_get($colors, 'bg', '#ffffff') }};">
                    <div class="theme-preview__bar" style="background: {{ data_get($colors, 'header', '#ffffff') }};">
                        <span class="theme-preview__dot" style="background: {{ data_get($colors, 'primary', '#6366f1') }};"></span>
                        <span class="theme-preview__line" style="background: {{ data_get($colors, 'text', '#111827') }};"></span>
                    </div>
                    <div class="theme-preview__body">
                        <span class="theme-preview__title" style="color: {{ data_get($colors, 'text', '#111827') }};">Заголовок</span>
                        <span class="theme-preview__text" style="color: {{ data_get($colors, 'text', '#111827') }};">Пример текста страницы</span>
                        <span class="theme-preview__btn"
                              style="background: {{ data_get($colors, 'primary', '#6366f1') }}; border-radius: {{ $radius }};">Кнопка</span>
                        <span class="theme-preview__chip"
                              style="background: {{ data_get($colors, 'accent', '#8b5cf6') }}; border-radius: {{ $radius }};"></span>
                    </div>
                    <div class="theme-preview__foot" style="background: {{ data_get($colors, 'footer', '#ffffff') }};"></div>
                </div>

                <div class="p-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="font-bold text-gray-900 dark:text-white truncate">{{ $theme->title }}</h2>
                        @if($theme->is_default)
                            <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5">
                                <i class="fas fa-check"></i> активна
                            </span>
                        @endif
                    </div>
                    <div class="text-xs font-mono text-gray-400 mt-0.5">{{ $theme->slug }}</div>

                    @if($note)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ $note }}</p>
                    @endif

                    <div class="flex flex-wrap gap-1 mt-3">
                        @foreach($swatches as $key => $label)
                            @if(!empty($colors[$key]))
                                <span class="theme-swatch" title="{{ $label }}: {{ $colors[$key] }}">
                                    <span class="theme-swatch__color" style="background: {{ $colors[$key] }};"></span>
                                    {{ $colors[$key] }}
                                </span>
                            @endif
                        @endforeach
                    </div>

                    <div class="flex flex-wrap gap-3 mt-3 text-xs text-gray-500 dark:text-gray-400">
                        <span><i class="fas fa-font text-indigo-500 mr-1"></i>{{ $fontName }}</span>
                        <span><i class="fas fa-icons text-indigo-500 mr-1"></i>{{ $iconModeLabels[$iconMode] ?? $iconMode }}</span>
                        <span><i class="fas fa-vector-square text-indigo-500 mr-1"></i>{{ $radius }}</span>
                    </div>

                    <div class="flex items-center gap-2 mt-4">
                        @if($theme->is_default)
                            <span class="inline-flex items-center justify-center gap-2 border border-gray-200 dark:border-gray-700
                                         text-gray-400 px-3 py-2 text-sm font-semibold flex-1 cursor-default">
                                <i class="fas fa-check"></i> Применена
                            </span>
                        @else
                            <button type="submit" form="apply-{{ $theme->id }}"
                                    class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                                           px-3 py-2 text-sm font-semibold shadow-sm transition flex-1">
                                <i class="fas fa-wand-magic-sparkles"></i> Применить
                            </button>
                        @endif

                        <a href="{{ route('admin.visual.themes.edit', $theme) }}"
                           class="inline-flex items-center justify-center w-9 h-9 border border-gray-300 dark:border-gray-600
                                  text-gray-600 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 transition"
                           title="Редактировать"><i class="fas fa-pen"></i></a>

                        <button type="submit" form="delete-{{ $theme->id }}"
                                class="inline-flex items-center justify-center w-9 h-9 border border-gray-300 dark:border-gray-600
                                       text-gray-600 dark:text-gray-300 hover:border-red-400 hover:text-red-600 transition
                                       {{ $theme->is_default ? 'opacity-40 cursor-not-allowed' : '' }}"
                                {{ $theme->is_default ? 'disabled' : '' }}
                                title="{{ $theme->is_default ? 'Сначала примените другую тему' : 'Удалить' }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Формы действий — вне карточек: вложенные формы HTML запрещает --}}
    @foreach($themes as $theme)
        @unless($theme->is_default)
            <form id="apply-{{ $theme->id }}" method="POST" action="{{ route('admin.visual.themes.apply', $theme) }}" class="hidden"
                  onsubmit="return confirm('Сделать тему «{{ addslashes($theme->title) }}» активной?');">
                @csrf @method('PATCH')
            </form>
            <form id="delete-{{ $theme->id }}" method="POST" action="{{ route('admin.visual.themes.destroy', $theme) }}" class="hidden"
                  onsubmit="return confirm('Удалить тему «{{ addslashes($theme->title) }}»? Это действие необратимо.');">
                @csrf @method('DELETE')
            </form>
        @endunless
    @endforeach

    <p class="admin-note mt-5 p-3">
        Тема меняет оформление сайта и акцент панели управления. Пять базовых тем создаются
        при установке — их можно править и дополнять своими.
    </p>
@endif
@endsection

@push('styles')
<style>
    .theme-card--active{ outline:2px solid var(--admin-primary); outline-offset:-2px; }

    .theme-preview{ height:150px; display:flex; flex-direction:column; border-bottom:1px solid #e5e7eb; }
    .theme-preview__bar{ height:26px; display:flex; align-items:center; gap:6px; padding:0 10px; }
    .theme-preview__dot{ width:10px; height:10px; border-radius:999px; flex:none; }
    .theme-preview__line{ height:4px; width:64px; opacity:.35; }
    .theme-preview__body{ flex:1; display:flex; flex-direction:column; align-items:flex-start; gap:6px; padding:12px 12px 0; }
    .theme-preview__title{ font-size:.85rem; font-weight:700; }
    .theme-preview__text{ font-size:.7rem; opacity:.7; }
    .theme-preview__btn{ font-size:.65rem; color:#fff; padding:3px 10px; margin-top:2px; }
    .theme-preview__chip{ width:38px; height:6px; opacity:.85; }
    .theme-preview__foot{ height:16px; }

    .theme-swatch{ display:inline-flex; align-items:center; gap:.3rem; font-size:.65rem; font-family:ui-monospace,monospace;
        color:#6b7280; border:1px solid #e5e7eb; padding:.1rem .35rem; }
    .dark .theme-swatch{ color:#9ca3af; border-color:#374151; }
    .theme-swatch__color{ width:.7rem; height:.7rem; border:1px solid rgba(0,0,0,.15); flex:none; }
</style>
@endpush
