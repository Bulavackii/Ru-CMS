@props([
    'model' => null,
    'fields' => [],      // ['title' => 'Заголовок', 'content' => ['label' => 'Текст', 'type' => 'textarea']]
])

@php
    use Illuminate\Support\Str;

    $originalLocale = config('app.content_locale', 'ru');
    $locales = array_values(array_diff(available_locales(), [$originalLocale]));
    $names = ['ru' => 'Русский', 'en' => 'English'];

    // Уже сохранённые переводы: [locale][field] => value
    $existing = [];
    if ($model && $model->exists) {
        foreach ($locales as $locale) {
            $existing[$locale] = $model->translationsFor($locale);
        }
    }

    $normalized = [];
    foreach ($fields as $field => $config) {
        $normalized[$field] = is_array($config)
            ? $config
            : ['label' => $config, 'type' => 'text'];
    }

    $filled = collect($existing)->filter(fn ($values) => collect($values)->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty())->keys();
@endphp

@if($locales !== [])
<div class="admin-card p-5 mt-5" x-data="{ locale: '{{ $locales[0] }}' }">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <i class="fas fa-language text-indigo-500"></i> Переводы
            @if($filled->isNotEmpty())
                <span class="normal-case font-normal tracking-normal text-gray-400">
                    заполнено: {{ $filled->implode(', ') }}
                </span>
            @endif
        </h2>

        <div class="flex flex-wrap gap-1">
            @foreach($locales as $locale)
                <button type="button" @click="locale = '{{ $locale }}'"
                        class="tr-tab"
                        :class="locale === '{{ $locale }}' ? 'tr-tab--active' : ''">
                    {!! locale_flag($locale) !!}
                    <span>{{ strtoupper($locale) }}</span>
                    @if($filled->contains($locale))
                        <i class="fas fa-check" style="font-size:.6rem; opacity:.7"></i>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <p class="admin-hint mb-4">
        Основной язык — {{ $names[$originalLocale] ?? $originalLocale }}: он берётся из полей выше.
        Пустой перевод означает «показывать оригинал», поэтому заполнять всё не обязательно.
    </p>

    @foreach($locales as $locale)
        <div x-show="locale === '{{ $locale }}'" x-cloak class="space-y-4">
            @foreach($normalized as $field => $config)
                @php
                    $value = old("translations.{$locale}.{$field}", $existing[$locale][$field] ?? '');
                    $inputId = "tr_{$locale}_{$field}";
                @endphp
                <div>
                    <label for="{{ $inputId }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $config['label'] }}
                        <span class="text-xs font-normal text-gray-400">— {{ $names[$locale] ?? strtoupper($locale) }}</span>
                    </label>

                    @if(($config['type'] ?? 'text') === 'textarea')
                        <textarea id="{{ $inputId }}" name="translations[{{ $locale }}][{{ $field }}]" rows="6"
                                  class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                         focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                  placeholder="Оставьте пустым — покажем оригинал">{{ $value }}</textarea>
                    @else
                        <input type="text" id="{{ $inputId }}" name="translations[{{ $locale }}][{{ $field }}]"
                               value="{{ $value }}"
                               class="w-full border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                               placeholder="Оставьте пустым — покажем оригинал">
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
</div>

@once
@push('styles')
<style>
    .tr-tab{ display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .6rem; font-size:.72rem;
        border:1px solid #d1d5db; background:#fff; color:#374151; transition:all .15s; }
    .tr-tab:hover{ border-color:#6366f1; color:#4338ca; }
    .tr-tab--active{ background:var(--admin-primary,#4f46e5); border-color:var(--admin-primary,#4f46e5); color:#fff; }
    .tr-tab .flag{ width:1rem; height:.7rem; display:inline-block; }
</style>
@endpush
@endonce
@endif
