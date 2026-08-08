{{--
    Конструктор форм.

    Устроен как конструктор каптчи: сборка мышью слева, живое превью справа
    (строится тем же сервисом, что выводит форму на сайте, — поэтому в превью
    видно ровно то, что получит посетитель), ниже список сохранённых форм с
    готовыми сниппетами для вставки.

    Примеры кода обёрнуты в директиву, отключающую компиляцию блока: без неё
    Blade ВЫПОЛНЯЕТ примеры, а не показывает их. Её имя, как и имена директив
    внутри примеров, нельзя писать здесь текстом — Blade компилирует и
    содержимое комментариев (см. CLAUDE.md, «Архитектурные грабли»).
--}}
@extends('layouts.admin')

@section('title', __('admin.forms.title'))

@section('content')
@php
    $typeMeta = [
        'text'       => ['title' => __('admin.forms.t_text'),       'icon' => 'fa-font'],
        'textarea'   => ['title' => __('admin.forms.t_textarea'),   'icon' => 'fa-align-left'],
        'email'      => ['title' => __('admin.forms.t_email'),      'icon' => 'fa-at'],
        'tel'        => ['title' => __('admin.forms.t_tel'),        'icon' => 'fa-phone'],
        'number'     => ['title' => __('admin.forms.t_number'),     'icon' => 'fa-hashtag'],
        'url'        => ['title' => __('admin.forms.t_url'),        'icon' => 'fa-link'],
        'date'       => ['title' => __('admin.forms.t_date'),       'icon' => 'fa-calendar-days'],
        'time'       => ['title' => __('admin.forms.t_time'),       'icon' => 'fa-clock'],
        'select'     => ['title' => __('admin.forms.t_select'),     'icon' => 'fa-caret-down'],
        'radio'      => ['title' => __('admin.forms.t_radio'),      'icon' => 'fa-circle-dot'],
        'checkbox'   => ['title' => __('admin.forms.t_checkbox'),   'icon' => 'fa-square-check'],
        'checkboxes' => ['title' => __('admin.forms.t_checkboxes'), 'icon' => 'fa-list-check'],
        'file'       => ['title' => __('admin.forms.t_file'),       'icon' => 'fa-paperclip'],
        'hidden'     => ['title' => __('admin.forms.t_hidden'),     'icon' => 'fa-eye-slash'],
        'heading'    => ['title' => __('admin.forms.t_heading'),    'icon' => 'fa-heading'],
        'paragraph'  => ['title' => __('admin.forms.t_paragraph'),  'icon' => 'fa-paragraph'],
        'consent'    => ['title' => __('admin.forms.t_consent'),    'icon' => 'fa-user-shield'],
    ];
@endphp

<div class="max-w-screen-2xl mx-auto"
     x-data="formBuilder(@js($blank), @js(array_values($typeMeta ? array_map(fn($k, $m) => ['type' => $k] + $m, array_keys($typeMeta), $typeMeta) : [])), @js($captchas))">

    {{-- Шапка раздела --}}
    <div class="admin-card mb-5">
        <div class="admin-accent-bar" aria-hidden="true"></div>
        <div class="p-5 flex flex-wrap items-center gap-4">
            <span class="admin-icon-badge" aria-hidden="true"><i class="fas fa-list-check"></i></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.forms.title') }}</h1>
                <p class="text-sm text-gray-500">{{ __('admin.forms.subtitle') }}</p>
            </div>
            <button type="button" class="fm-btn" @click="reset()" x-show="editing" x-cloak>
                <i class="fas fa-plus"></i> {{ __('admin.forms.new') }}
            </button>
        </div>
    </div>

    {{-- ═══ Конструктор ═══ --}}
    <form method="POST"
          :action="editing ? formUrl(editing) : @js(route('admin.forms.store'))"
          @submit="submitting = true"
          class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)]">
        @csrf
        <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

        {{-- ── Левая колонка: сборка ── --}}
        <section class="admin-card p-5 min-w-0">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
                <span x-text="editing ? @js(__('admin.forms.editing')) : @js(__('admin.forms.building'))"></span>
            </h2>

            <div class="grid gap-3 sm:grid-cols-2 mb-4">
                <label class="fm-field">
                    <span class="fm-label">{{ __('admin.forms.f_title') }}</span>
                    <input type="text" name="title" x-model="form.title" required maxlength="255" class="fm-input">
                </label>
                <label class="fm-field">
                    <span class="fm-label">{{ __('admin.forms.f_desc') }}</span>
                    <input type="text" name="description" x-model="form.description" maxlength="500" class="fm-input">
                </label>
            </div>

            {{-- Поля --}}
            <div class="flex items-center justify-between gap-3 mb-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ __('admin.forms.fields') }}</h3>
                <span class="text-xs text-gray-400" x-text="form.fields.length + ' ' + @js(__('admin.forms.fields_short'))"></span>
            </div>

            <div class="fm-list">
                <template x-for="(field, index) in form.fields" :key="field.uid">
                    <div class="fm-item" :class="{ 'is-open': opened === field.uid }">
                        {{-- Свёрнутая строка --}}
                        <div class="fm-head">
                            <span class="fm-grip" :title="@js(__('admin.forms.move'))">
                                <button type="button" class="fm-move" @click="move(index, -1)" :disabled="index === 0">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                                <button type="button" class="fm-move" @click="move(index, 1)" :disabled="index === form.fields.length - 1">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </span>

                            <span class="fm-type-ico"><i class="fas" :class="iconOf(field.type)"></i></span>

                            <button type="button" class="fm-title" @click="opened = opened === field.uid ? null : field.uid">
                                <span x-text="field.label || titleOf(field.type)"></span>
                                <code class="fm-code" x-text="titleOf(field.type)"></code>
                                <span class="fm-req-mark" x-show="field.required" x-cloak>*</span>
                            </button>

                            <button type="button" class="fm-icon-btn" @click="duplicateField(index)" :title="@js(__('admin.forms.act_duplicate'))">
                                <i class="fa-regular fa-clone"></i>
                            </button>
                            <button type="button" class="fm-icon-btn fm-icon-btn--danger" @click="removeField(index)" :title="@js(__('admin.forms.act_delete'))">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>

                        {{-- Раскрытые настройки поля --}}
                        <div class="fm-body" x-show="opened === field.uid" x-cloak>
                            <input type="hidden" :name="'fields[' + index + '][type]'" :value="field.type">
                            <input type="hidden" :name="'fields[' + index + '][name]'" :value="field.name">

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="fm-field">
                                    <span class="fm-label">{{ __('admin.forms.f_label') }}</span>
                                    <input type="text" :name="'fields[' + index + '][label]'" x-model="field.label" maxlength="255" class="fm-input">
                                </label>

                                <label class="fm-field">
                                    <span class="fm-label">{{ __('admin.forms.f_type') }}</span>
                                    <select x-model="field.type" @change="onTypeChange(field)" class="fm-input">
                                        <template x-for="meta in types" :key="meta.type">
                                            <option :value="meta.type" x-text="meta.title"></option>
                                        </template>
                                    </select>
                                </label>

                                <label class="fm-field" x-show="!isDecorative(field.type)">
                                    <span class="fm-label">{{ __('admin.forms.f_placeholder') }}</span>
                                    <input type="text" :name="'fields[' + index + '][placeholder]'" x-model="field.placeholder" maxlength="255" class="fm-input">
                                </label>

                                <label class="fm-field" x-show="!isDecorative(field.type)">
                                    <span class="fm-label">{{ __('admin.forms.f_hint') }}</span>
                                    <input type="text" :name="'fields[' + index + '][hint]'" x-model="field.hint" maxlength="255" class="fm-input">
                                </label>

                                <label class="fm-field" x-show="field.type === 'hidden'" x-cloak>
                                    <span class="fm-label">{{ __('admin.forms.f_value') }}</span>
                                    <input type="text" :name="'fields[' + index + '][value]'" x-model="field.value" maxlength="255" class="fm-input">
                                </label>

                                <label class="fm-field" x-show="!isDecorative(field.type)">
                                    <span class="fm-label">{{ __('admin.forms.f_width') }}</span>
                                    <select :name="'fields[' + index + '][width]'" x-model="field.width" class="fm-input">
                                        <option value="full">{{ __('admin.forms.w_full') }}</option>
                                        <option value="half">{{ __('admin.forms.w_half') }}</option>
                                    </select>
                                </label>
                            </div>

                            {{-- Варианты для списков --}}
                            <div class="mt-3" x-show="hasOptions(field.type)" x-cloak>
                                <span class="fm-label">{{ __('admin.forms.f_options') }}</span>
                                <template x-for="(option, oi) in field.options" :key="oi">
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <input type="text" :name="'fields[' + index + '][options][]'"
                                               x-model="field.options[oi]" maxlength="255" class="fm-input">
                                        <button type="button" class="fm-icon-btn fm-icon-btn--danger" @click="field.options.splice(oi, 1)">
                                            <i class="fas fa-xmark"></i>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" class="fm-add-opt" @click="field.options.push('')">
                                    <i class="fas fa-plus"></i> {{ __('admin.forms.add_option') }}
                                </button>
                            </div>

                            <label class="fm-check mt-3" x-show="!isDecorative(field.type)">
                                <input type="checkbox" :name="'fields[' + index + '][required]'" value="1" x-model="field.required">
                                <span>{{ __('admin.forms.f_required') }}</span>
                            </label>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Палитра типов: клик добавляет поле в конец --}}
            <div class="fm-palette">
                <span class="fm-palette-label">{{ __('admin.forms.add_field') }}</span>
                <template x-for="meta in types" :key="meta.type">
                    <button type="button" class="fm-chip" @click="addField(meta.type)" :title="meta.title">
                        <i class="fas" :class="meta.icon"></i>
                        <span x-text="meta.title"></span>
                    </button>
                </template>
            </div>

            {{-- Настройки формы --}}
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mt-5 mb-2">{{ __('admin.forms.settings') }}</h3>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="fm-field">
                    <span class="fm-label">{{ __('admin.forms.s_submit') }}</span>
                    <input type="text" name="settings[submit_label]" x-model="form.settings.submit_label" maxlength="64" class="fm-input"
                           placeholder="{{ __('forms.submit') }}">
                </label>

                <label class="fm-field">
                    <span class="fm-label">{{ __('admin.forms.s_notify') }}</span>
                    <input type="text" name="settings[notify_email]" x-model="form.settings.notify_email" maxlength="255" class="fm-input"
                           placeholder="mail@example.com">
                    <small class="fm-hint">{{ __('admin.forms.s_notify_hint') }}</small>
                </label>

                <label class="fm-field sm:col-span-2">
                    <span class="fm-label">{{ __('admin.forms.s_success') }}</span>
                    <input type="text" name="settings[success_message]" x-model="form.settings.success_message" maxlength="500" class="fm-input"
                           placeholder="{{ __('forms.sent') }}">
                </label>

                <label class="fm-field">
                    <span class="fm-label">{{ __('admin.forms.s_redirect') }}</span>
                    <input type="text" name="settings[redirect_url]" x-model="form.settings.redirect_url" maxlength="255" class="fm-input"
                           placeholder="/spasibo">
                    <small class="fm-hint">{{ __('admin.forms.s_redirect_hint') }}</small>
                </label>

                <label class="fm-field">
                    <span class="fm-label">{{ __('admin.forms.s_captcha') }}</span>
                    <select name="settings[captcha]" x-model="form.settings.captcha" class="fm-input">
                        <option value="">{{ __('admin.forms.s_captcha_off') }}</option>
                        <template x-for="preset in captchas" :key="preset.slug">
                            <option :value="preset.slug" x-text="preset.name"></option>
                        </template>
                    </select>
                    <small class="fm-hint" x-show="!captchas.length" x-cloak>{{ __('admin.forms.s_captcha_none') }}</small>
                </label>

                <label class="fm-field sm:col-span-2">
                    <span class="fm-label">{{ __('admin.forms.s_note') }}</span>
                    <input type="text" name="settings[note]" x-model="form.settings.note" maxlength="255" class="fm-input">
                </label>
            </div>

            <div class="flex flex-wrap gap-4 mt-3">
                <label class="fm-check">
                    <input type="checkbox" name="settings[columns]" value="1" x-model="form.settings.columns">
                    <span>{{ __('admin.forms.s_columns') }}</span>
                </label>
                <label class="fm-check">
                    <input type="checkbox" name="settings[show_title]" value="1" x-model="form.settings.show_title">
                    <span>{{ __('admin.forms.s_show_title') }}</span>
                </label>
                <label class="fm-check">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active">
                    <span>{{ __('admin.forms.s_active') }}</span>
                </label>
            </div>

            <div class="flex flex-wrap items-center gap-2 mt-5">
                <button type="submit" class="fm-btn" :disabled="submitting || !form.fields.length">
                    <i class="fas fa-floppy-disk"></i>
                    <span x-text="editing ? @js(__('admin.forms.save')) : @js(__('admin.forms.create'))"></span>
                </button>
                <button type="button" class="fm-btn fm-btn--ghost" @click="reset()" x-show="editing" x-cloak>
                    {{ __('admin.cancel') }}
                </button>
            </div>
        </section>

        {{-- ── Правая колонка: превью ── --}}
        <section class="admin-card p-5 min-w-0">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">{{ __('admin.forms.preview') }}</h2>

            <div class="fm-preview">
                <div x-show="loading" class="text-xs text-gray-400">{{ __('admin.forms.building_preview') }}</div>
                <div x-show="!loading" x-html="preview"></div>
            </div>

            <p class="fm-note mt-3">{{ __('admin.forms.preview_note') }}</p>

            <div x-cloak x-show="error" class="admin-note p-3 mt-3 text-xs" x-text="error"></div>
        </section>
    </form>

    {{-- ═══ Сохранённые формы ═══ --}}
    <section class="admin-card p-5 mt-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">{{ __('admin.forms.saved') }}</h2>

        @forelse($forms as $item)
            <div class="fm-saved">
                <span class="fm-type-ico"><i class="fas fa-list-check"></i></span>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <strong class="text-gray-900 dark:text-white">{{ $item->title }}</strong>
                        <code class="fm-code">{{ count((array) $item->fields) }} {{ __('admin.forms.fields_short') }}</code>
                        @unless($item->is_active)
                            <span class="fm-off">{{ __('admin.forms.form_off') }}</span>
                        @endunless
                    </div>

                    <div class="fm-stats">
                        <a href="{{ route('admin.forms.submissions', $item) }}" class="fm-stat-link">
                            <b>{{ $item->submissions_count }}</b> {{ __('admin.forms.stat_total') }}
                        </a>
                        @if($item->unread_count)
                            <span class="fm-unread">{{ $item->unread_count }} {{ __('admin.forms.stat_new') }}</span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        {{-- Копирование через @js(): @json внутри onclick рвёт
                             атрибут на первой же двойной кавычке --}}
                        <button type="button" class="fm-copy"
                                onclick="navigator.clipboard.writeText(@js($item->shortcode())).then(() => window.toast && toast(@js(__('admin.forms.copied_shortcode'))))">
                            <i class="fa-regular fa-copy"></i>
                            <code>{{ $item->shortcode() }}</code>
                        </button>

                        <button type="button" class="fm-copy"
                                onclick="navigator.clipboard.writeText(@js($item->bladeSnippet())).then(() => window.toast && toast(@js(__('admin.forms.copied_blade'))))">
                            <i class="fa-regular fa-copy"></i> {{ __('admin.forms.for_template') }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 flex-none">
                    <a href="{{ route('admin.forms.submissions', $item) }}" class="fm-icon-btn" title="{{ __('admin.forms.act_submissions') }}">
                        <i class="fa-regular fa-envelope"></i>
                    </a>

                    <button type="button" class="fm-icon-btn" title="{{ __('admin.forms.act_edit') }}"
                            @click="edit(@js([
                                'id'          => $item->id,
                                'title'       => $item->title,
                                'description' => (string) $item->description,
                                'is_active'   => (bool) $item->is_active,
                                'fields'      => $item->normalizedFields(),
                                'settings'    => (array) $item->settings,
                            ]))">
                        <i class="fas fa-pen"></i>
                    </button>

                    <form method="POST" action="{{ route('admin.forms.duplicate', $item) }}">
                        @csrf
                        <button type="submit" class="fm-icon-btn" title="{{ __('admin.forms.act_duplicate') }}"><i class="fa-regular fa-clone"></i></button>
                    </form>

                    <form method="POST" action="{{ route('admin.forms.destroy', $item) }}"
                          onsubmit="return confirm(@js(__('admin.forms.confirm_delete', ['title' => $item->title])))">
                        @csrf @method('DELETE')
                        <button type="submit" class="fm-icon-btn fm-icon-btn--danger" title="{{ __('admin.forms.act_delete') }}"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">{{ __('admin.forms.empty') }}</p>
        @endforelse
    </section>

    {{-- ═══ Памятка ═══ --}}
    <section class="admin-card p-5 mt-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">{{ __('admin.forms.howto') }}</h2>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <h3 class="fm-h3">{{ __('admin.forms.h1') }}</h3>
                <p class="fm-p">{{ __('admin.forms.h1_text') }}</p>
                <pre class="fm-pre"><code>[form slug="obratnaya-svyaz"]</code></pre>
            </div>

            <div>
                <h3 class="fm-h3">{{ __('admin.forms.h2') }}</h3>
                <p class="fm-p">{{ __('admin.forms.h2_text') }}</p>
                @verbatim
                    <pre class="fm-pre"><code>{!! form_render('obratnaya-svyaz') !!}</code></pre>
                @endverbatim
            </div>

            <div>
                <h3 class="fm-h3">{{ __('admin.forms.h3') }}</h3>
                <p class="fm-p">{{ __('admin.forms.h3_text') }}</p>
            </div>

            <div>
                <h3 class="fm-h3">{{ __('admin.forms.h4') }}</h3>
                <p class="fm-p">{{ __('admin.forms.h4_text') }}</p>
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в сборке проекта нет opacity-модификаторов,
       произвольных значений и половины цветов v3 (см. CLAUDE.md). */
    .fm-field { display:flex; flex-direction:column; gap:4px; min-width:0 }
    .fm-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#6b7280 }
    .fm-hint  { font-size:.72rem; color:#9ca3af; line-height:1.45 }
    .fm-input { width:100%; padding:7px 10px; font-size:.85rem; color:#111827; background:#fff;
                border:1px solid #d1d5db; outline:none; transition:border-color .15s ease }
    .fm-input:focus { border-color:#6366f1 }

    .fm-list { display:flex; flex-direction:column; gap:6px }
    .fm-item { border:1px solid #e5e7eb; background:#fff }
    .fm-item.is-open { border-color:#c7cbf5 }
    .fm-head { display:flex; align-items:center; gap:8px; padding:7px 10px }
    .fm-grip { display:flex; flex-direction:column; gap:1px }
    .fm-move { width:18px; height:13px; display:inline-flex; align-items:center; justify-content:center;
               font-size:9px; color:#9ca3af; border:1px solid #e5e7eb; background:#fff; cursor:pointer }
    .fm-move:disabled { opacity:.4; cursor:default }
    .fm-type-ico { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
                   font-size:12px; color:#4f46e5; background:#eef2ff; flex:0 0 auto }
    .fm-title { display:flex; align-items:center; gap:8px; flex:1 1 auto; min-width:0;
                font-size:.85rem; text-align:left; background:none; border:0; cursor:pointer; color:#111827 }
    .fm-req-mark { color:#dc2626; font-weight:700 }
    .fm-code { padding:1px 6px; font-size:.68rem; color:#4b5563; background:#f3f4f6 }
    .fm-body { padding:10px; border-top:1px solid #e5e7eb; background:#fafafa }

    .fm-icon-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px;
                   font-size:12px; color:#4b5563; background:#fff; border:1px solid #e5e7eb; cursor:pointer;
                   transition:background .15s ease, color .15s ease }
    .fm-icon-btn:hover { background:#f3f4f6; color:#111827 }
    .fm-icon-btn--danger:hover { background:#dc2626; border-color:#dc2626; color:#fff }

    .fm-palette { display:flex; flex-wrap:wrap; align-items:center; gap:5px; margin-top:12px;
                  padding-top:12px; border-top:1px dashed #e5e7eb }
    .fm-palette-label { width:100%; margin-bottom:2px; font-size:.72rem; font-weight:700;
                        text-transform:uppercase; letter-spacing:.04em; color:#9ca3af }
    .fm-chip { display:inline-flex; align-items:center; gap:5px; padding:5px 9px; font-size:.76rem;
               color:#374151; background:#fff; border:1px solid #e5e7eb; cursor:pointer;
               transition:border-color .15s ease, color .15s ease }
    .fm-chip:hover { border-color:#6366f1; color:#4338ca }
    .fm-add-opt { margin-top:6px; padding:5px 9px; font-size:.76rem; color:#4338ca;
                  background:#eef2ff; border:0; cursor:pointer }

    .fm-check { display:inline-flex; align-items:center; gap:7px; font-size:.82rem; color:#374151; cursor:pointer }
    .fm-check input { width:15px; height:15px; accent-color:#6366f1 }

    .fm-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; font-size:.85rem;
              font-weight:600; color:#fff; background:#4f46e5; border:0; cursor:pointer;
              transition:background .15s ease }
    .fm-btn:hover { background:#4338ca }
    .fm-btn[disabled] { opacity:.55; cursor:default }
    .fm-btn--ghost { color:#4b5563; background:#fff; border:1px solid #d1d5db }
    .fm-btn--ghost:hover { background:#f3f4f6 }

    .fm-preview { padding:14px; border:1px dashed #d1d5db; background:#fff; min-height:120px }
    .fm-note { font-size:.75rem; color:#9ca3af; line-height:1.5 }

    .fm-saved { display:flex; align-items:flex-start; gap:12px; padding:11px 0; border-top:1px solid #f3f4f6 }
    .fm-saved:first-of-type { border-top:0 }
    .fm-off { padding:1px 7px; font-size:.68rem; color:#92400e; background:#fef3c7 }
    .fm-stats { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-top:3px;
                font-size:.76rem; color:#6b7280 }
    .fm-stat-link { color:#4338ca }
    .fm-unread { padding:1px 7px; font-size:.68rem; color:#fff; background:#dc2626 }
    .fm-copy { display:inline-flex; align-items:center; gap:6px; padding:3px 8px; font-size:.72rem;
               color:#4b5563; background:#f9fafb; border:1px solid #e5e7eb; cursor:pointer }
    .fm-copy:hover { border-color:#6366f1; color:#4338ca }

    .fm-h3 { margin:0 0 4px; font-size:.85rem; font-weight:700; color:#111827 }
    .fm-p  { margin:0; font-size:.8rem; color:#6b7280; line-height:1.55 }
    .fm-pre { margin:8px 0 0; padding:9px 11px; font-size:.75rem; color:#e5e7eb; background:#1f2937; overflow-x:auto }
</style>
@endpush

@push('scripts')
<script>
    /**
     * Конструктор форм.
     *
     * Превью строит СЕРВЕР тем же сервисом, что выводит форму на сайте:
     * рисовать её второй раз на клиенте значило бы завести вторую разметку,
     * которая неизбежно разойдётся с настоящей.
     */
    function formBuilder(blank, types, captchas) {
        return {
            blank: blank,
            types: types,
            captchas: captchas,
            form: null,
            editing: null,
            opened: null,
            preview: '',
            loading: false,
            error: '',
            submitting: false,
            timer: null,
            requestId: 0,

            init() {
                this.form = this.prepare(this.blank);
                this.refresh();

                // Следим за всей сборкой: меняется что угодно — превью
                // пересобирается. Задержка гасит запрос на каждую букву.
                this.$watch('form', () => this.schedule(), { deep: true });
            },

            /** Каждому полю нужен устойчивый ключ для x-for. */
            prepare(source) {
                var form = JSON.parse(JSON.stringify(source));

                form.fields = (form.fields || []).map((field) => Object.assign({
                    placeholder: '', hint: '', value: '', required: false, width: 'full', options: []
                }, field, { uid: this.uid() }));

                form.settings = Object.assign({
                    submit_label: '', success_message: '', note: '',
                    notify_email: '', redirect_url: '', captcha: '',
                    columns: true, show_title: true
                }, form.settings || {});

                return form;
            },

            uid() {
                return 'f' + Math.random().toString(36).slice(2, 9);
            },

            titleOf(type) {
                var meta = this.types.find((item) => item.type === type);
                return meta ? meta.title : type;
            },

            iconOf(type) {
                var meta = this.types.find((item) => item.type === type);
                return meta ? meta.icon : 'fa-font';
            },

            hasOptions(type) {
                return ['select', 'radio', 'checkboxes'].indexOf(type) !== -1;
            },

            isDecorative(type) {
                return ['heading', 'paragraph'].indexOf(type) !== -1;
            },

            addField(type) {
                var field = {
                    uid: this.uid(), type: type, name: '', label: this.titleOf(type),
                    placeholder: '', hint: '', value: '', required: false, width: 'full',
                    options: this.hasOptions(type) ? [@js(__('admin.forms.option_example'))] : []
                };

                this.form.fields.push(field);
                this.opened = field.uid;
            },

            duplicateField(index) {
                var copy = JSON.parse(JSON.stringify(this.form.fields[index]));
                copy.uid = this.uid();
                // Имя очищаем: два поля с одним именем затирали бы друг друга
                // в заявке, и второй ответ просто не сохранился бы.
                copy.name = '';
                this.form.fields.splice(index + 1, 0, copy);
            },

            removeField(index) {
                this.form.fields.splice(index, 1);
            },

            move(index, delta) {
                var target = index + delta;

                if (target < 0 || target >= this.form.fields.length) {
                    return;
                }

                var moved = this.form.fields.splice(index, 1)[0];
                this.form.fields.splice(target, 0, moved);
            },

            /** Смена типа: варианты нужны не всем, разметке — не нужны вовсе. */
            onTypeChange(field) {
                if (this.hasOptions(field.type) && !field.options.length) {
                    field.options = [@js(__('admin.forms.option_example'))];
                }

                if (!this.hasOptions(field.type)) {
                    field.options = [];
                }
            },

            edit(item) {
                this.editing = item.id;
                this.form = this.prepare(item);
                this.opened = null;
                this.refresh();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            reset() {
                this.editing = null;
                this.form = this.prepare(this.blank);
                this.opened = null;
                this.refresh();
            },

            formUrl(id) {
                return @js(route('admin.forms.index')) + '/' + id;
            },

            schedule() {
                window.clearTimeout(this.timer);
                this.timer = window.setTimeout(() => this.refresh(), 350);
            },

            refresh() {
                this.loading = true;
                this.error = '';

                // Счётчик запросов: медленный ответ на прежнюю сборку мог бы
                // прийти после быстрого на новую и перетереть свежее превью.
                var ticket = ++this.requestId;

                fetch(@js(route('admin.forms.preview')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: this.form.title,
                        description: this.form.description,
                        fields: this.form.fields,
                        settings: this.form.settings
                    })
                })
                .then((response) => response.json())
                .then((data) => {
                    if (ticket !== this.requestId) {
                        return;
                    }

                    this.preview = data.html || '';
                    this.loading = false;
                })
                .catch(() => {
                    if (ticket !== this.requestId) {
                        return;
                    }

                    this.error = @js(__('admin.forms.preview_failed'));
                    this.loading = false;
                });
            }
        };
    }
</script>
@endpush
