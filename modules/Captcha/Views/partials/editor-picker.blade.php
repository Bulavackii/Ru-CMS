{{--
    Вставка сохранённой сборки каптчи в текст материала.

    Подключается под полем содержимого в редакторах Новостей и Страниц.
    Вставляет шорткод [captcha preset="…"] в позицию курсора активного
    редактора TinyMCE; если редактор ещё не поднялся — прямо в textarea.

    Если сохранённых сборок нет, контрол НЕ показывается: пустой выпадающий
    список ничего не объясняет. Вместо него — ссылка в конструктор.

    Раскрывается шорткод при выводе материала (render_shortcodes в
    app/helpers.php): в сохранённом HTML вызовы Blade уже не работают.
--}}
@php
    $captchaPresets = \Illuminate\Support\Facades\Route::has('admin.captcha.index')
        ? \Modules\Captcha\Models\CaptchaPreset::activeList()
        : collect();
@endphp

<div class="cap-embed" x-data="captchaEmbed(@js($captchaPresets->pluck('slug', 'name')->toArray()))">
    @if($captchaPresets->isNotEmpty())
        <i class="fas fa-shield-halved cap-embed-ico" aria-hidden="true"></i>
        <span class="cap-embed-label">{{ __('admin.sections.captcha') }}:</span>

        <select x-model="slug" class="cap-embed-select" aria-label="{{ __('admin.captcha.preset') }}">
            @foreach($captchaPresets as $preset)
                <option value="{{ $preset->slug }}">{{ $preset->name }}</option>
            @endforeach
        </select>

        <button type="button" @click="insert()" class="cap-embed-btn">
            <i class="fas fa-plus"></i> {{ __('admin.captcha.insert') }}
        </button>

        <span x-cloak x-show="done" class="cap-embed-done">{{ __('admin.captcha.inserted') }}</span>
    @else
        <i class="fas fa-shield-halved cap-embed-ico" aria-hidden="true"></i>
        <span class="cap-embed-label">
            {{ __('admin.captcha.picker_hint') }}
            <a href="{{ route('admin.captcha.index') }}" class="cap-embed-link">{{ __('admin.captcha.build') }}</a>.
        </span>
    @endif
</div>

@once
@push('styles')
<style>
    .cap-embed{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-top:.6rem;
        padding:.5rem .7rem;font-size:.78rem;border:1px solid #e5e7eb;background:rgba(17,24,39,.02)}
    .dark .cap-embed{border-color:#374151;background:rgba(255,255,255,.03)}
    .cap-embed-ico{color:var(--admin-primary,#6366f1)}
    .cap-embed-label{color:#6b7280}
    .dark .cap-embed-label{color:#9ca3af}
    .cap-embed-select{padding:.25rem .45rem;font-size:.78rem;color:#111827;background:#fff;
        border:1px solid #d1d5db;max-width:14rem}
    .dark .cap-embed-select{color:#e5e7eb;background:#111827;border-color:#374151}
    .cap-embed-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .6rem;
        font-size:.75rem;font-weight:600;color:var(--admin-on-primary,#fff);border:0;cursor:pointer;
        background:var(--admin-primary,#6366f1)}
    .cap-embed-btn:hover{filter:brightness(1.1)}
    .cap-embed-done{font-size:.72rem;font-weight:600;color:#059669}
    .cap-embed-link{color:var(--admin-primary,#4f46e5);font-weight:600}
    .cap-embed-link:hover{text-decoration:underline}
</style>
@endpush

@push('scripts')
<script>
function captchaEmbed(presets) {
    const slugs = Object.values(presets);

    return {
        slug: slugs[0] || '',
        done: false,

        insert() {
            if (!this.slug) return;

            const shortcode = '[captcha preset="' + this.slug + '"]';

            // Основной путь — вставка в позицию курсора активного редактора
            const editor = window.tinymce && window.tinymce.activeEditor;
            if (editor && !editor.isHidden()) {
                editor.insertContent('<p>' + shortcode + '</p>');
            } else {
                // Редактор ещё не поднялся (или отключён) — пишем прямо в поле
                const area = document.querySelector('textarea[name="content"]');
                if (!area) return;

                const at = area.selectionStart ?? area.value.length;
                area.value = area.value.slice(0, at) + shortcode + area.value.slice(at);
            }

            this.done = true;
            setTimeout(() => { this.done = false; }, 2000);
        },
    };
}
</script>
@endpush
@endonce
