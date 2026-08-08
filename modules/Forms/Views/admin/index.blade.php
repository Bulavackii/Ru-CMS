{{--
    Конструктор форм.

    Первая версия собиралась как конструктор каптчи — и оказалась неудобной:
    поля добавлялись из палитры ПОД списком (не видно, куда встанет), порядок
    менялся кнопками по одному шагу, а чтобы переименовать поле, его надо было
    развернуть. Самое частое действие стоило двух нажатий.

    Здесь: подпись правится прямо в строке, порядок — перетаскиванием, палитра
    разложена по смыслу и стоит НАД списком, новое поле встаёт после выбранного,
    а редко используемые настройки убраны под «Дополнительно».

    Примеры кода обёрнуты в директиву, отключающую компиляцию блока: без неё
    Blade ВЫПОЛНЯЕТ примеры, а не показывает их. Её имя, как и имена директив
    внутри примеров, нельзя писать здесь текстом — Blade компилирует и
    содержимое комментариев (см. CLAUDE.md, «Архитектурные грабли»).
--}}
@extends('layouts.admin')

@section('title', __('admin.forms.title'))

@section('content')
@php
    // Палитра — ВОСЕМЬ видов, а не семнадцать типов. Почта, телефон, число,
    // ссылка, дата и время — это одна и та же строка с разной проверкой, и
    // выкладывать их отдельными кнопками значило строить стену, в которой
    // ничего не найти. Вид выбирается кнопкой, уточнение — списком в карточке.
    $kinds = [
        'text'    => ['title' => __('admin.forms.k_text'),    'icon' => 'fa-font',        'desc' => __('admin.forms.k_text_d'),
                      'variants' => ['text', 'email', 'tel', 'number', 'url', 'date', 'time']],
        'textarea'=> ['title' => __('admin.forms.k_textarea'),'icon' => 'fa-align-left',  'desc' => __('admin.forms.k_textarea_d'),
                      'variants' => ['textarea']],
        'choice'  => ['title' => __('admin.forms.k_choice'),  'icon' => 'fa-list-ul',     'desc' => __('admin.forms.k_choice_d'),
                      'variants' => ['select', 'radio', 'checkboxes']],
        'checkbox'=> ['title' => __('admin.forms.k_checkbox'),'icon' => 'fa-square-check','desc' => __('admin.forms.k_checkbox_d'),
                      'variants' => ['checkbox']],
        'file'    => ['title' => __('admin.forms.k_file'),    'icon' => 'fa-paperclip',   'desc' => __('admin.forms.k_file_d'),
                      'variants' => ['file']],
        'consent' => ['title' => __('admin.forms.k_consent'), 'icon' => 'fa-user-shield', 'desc' => __('admin.forms.k_consent_d'),
                      'variants' => ['consent']],
        'heading' => ['title' => __('admin.forms.k_heading'), 'icon' => 'fa-heading',     'desc' => __('admin.forms.k_heading_d'),
                      'variants' => ['heading', 'paragraph']],
        'hidden'  => ['title' => __('admin.forms.k_hidden'),  'icon' => 'fa-eye-slash',   'desc' => __('admin.forms.k_hidden_d'),
                      'variants' => ['hidden']],
    ];

    // Названия уточнений: они же подписи выпадающего списка в карточке.
    $variants = [
        'text'       => __('admin.forms.t_text'),
        'email'      => __('admin.forms.t_email'),
        'tel'        => __('admin.forms.t_tel'),
        'number'     => __('admin.forms.t_number'),
        'url'        => __('admin.forms.t_url'),
        'date'       => __('admin.forms.t_date'),
        'time'       => __('admin.forms.t_time'),
        'textarea'   => __('admin.forms.t_textarea'),
        'select'     => __('admin.forms.t_select'),
        'radio'      => __('admin.forms.t_radio'),
        'checkboxes' => __('admin.forms.t_checkboxes'),
        'checkbox'   => __('admin.forms.t_checkbox'),
        'file'       => __('admin.forms.t_file'),
        'consent'    => __('admin.forms.t_consent'),
        'heading'    => __('admin.forms.t_heading'),
        'paragraph'  => __('admin.forms.t_paragraph'),
        'hidden'     => __('admin.forms.t_hidden'),
    ];

    // Пояснение к каждому конкретному типу — оно и раньше было, просто теперь
    // показывается для выбранного уточнения.
    $meta = [];
    foreach ($variants as $type => $title) {
        $meta[$type] = ['title' => $title, 'desc' => __('admin.forms.d_' . $type)];
    }

    // Ширина больше не спрашивается: короткие ответы встают в половину строки,
    // длинные — во всю. Это вёрстка, а не смысл поля, и держать ради неё
    // настройку у каждого из семнадцати типов незачем.
    $halfWidth = ['text', 'email', 'tel', 'number', 'url', 'date', 'time', 'select'];

@endphp

<div class="max-w-screen-2xl mx-auto"
     x-data="formBuilder(@js($blank), @js($meta), @js($kinds), @js($halfWidth), @js($captchas), @js($starters))">

    {{-- Шапка раздела --}}
    <div class="admin-card mb-4">
        <div class="admin-accent-bar" aria-hidden="true"></div>
        <div class="p-4 flex flex-wrap items-center gap-4">
            <span class="admin-icon-badge" aria-hidden="true"><i class="fas fa-list-check"></i></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.forms.title') }}</h1>
                <p class="text-sm text-gray-500">{{ __('admin.forms.subtitle') }}</p>
            </div>
            <button type="button" class="fm-btn fm-btn--ghost" @click="reset()" x-show="editing" x-cloak>
                <i class="fas fa-plus"></i> {{ __('admin.forms.new') }}
            </button>
        </div>
    </div>

    <div class="fm-layout">

    <form method="POST"
          :action="editing ? formUrl(editing) : @js(route('admin.forms.store'))"
          @submit="submitting = true"
          class="min-w-0">
        @csrf
        <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

        {{-- ══════════ Сборка ══════════ --}}
        <section class="admin-card p-4 min-w-0">

            {{-- Название и описание — в одну строку, это не главное на экране --}}
            <div class="fm-top">
                <label class="fm-field fm-field--grow">
                    <span class="fm-label">{{ __('admin.forms.f_title') }}</span>
                    <input type="text" name="title" x-model="form.title" required maxlength="255"
                           class="fm-input fm-input--big" placeholder="{{ __('admin.forms.f_title_ph') }}">
                </label>
                <label class="fm-field fm-field--grow">
                    <span class="fm-label">{{ __('admin.forms.f_desc') }}</span>
                    <input type="text" name="description" x-model="form.description" maxlength="500"
                           class="fm-input" placeholder="{{ __('admin.forms.f_desc_ph') }}">
                </label>
            </div>

            {{-- Иконка формы. Компактной кнопкой с выпадающим набором: лента из
                 тридцати кнопок занимала пол-экрана и перетягивала внимание с
                 названия формы, ради которого этот блок и существует. --}}
            <div class="fm-field" style="margin-bottom:12px" x-data="{ open: false }" @click.outside="open = false">
                <span class="fm-label">{{ __('admin.forms.f_form_icon') }}</span>
                <input type="hidden" name="settings[icon]" :value="form.settings.icon">

                <div class="fm-picker">
                    <button type="button" class="fm-picker-btn" @click="open = !open">
                        <i class="fas" :class="form.settings.icon ? form.settings.icon.replace('fas ', '') : 'fa-ban'"></i>
                        <span x-text="form.settings.icon ? form.settings.icon.replace('fas fa-', '') : @js(__('admin.forms.icon_none'))"></span>
                        <i class="fas fa-chevron-down fm-picker-arrow"></i>
                    </button>

                    <div class="fm-picker-drop" x-show="open" x-cloak>
                        <button type="button" class="fm-ico-btn" :class="{ 'is-on': !form.settings.icon }"
                                @click="form.settings.icon = ''; open = false" :title="@js(__('admin.forms.icon_none'))">
                            <i class="fas fa-ban"></i>
                        </button>
                        <template x-for="name in icons" :key="name">
                            <button type="button" class="fm-ico-btn" :class="{ 'is-on': form.settings.icon === 'fas ' + name }"
                                    @click="form.settings.icon = 'fas ' + name; open = false">
                                <i class="fas" :class="name"></i>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Быстрый старт: пустая форма с нуля — самый долгий путь --}}
            <div class="fm-starters" x-show="!editing && !form.fields.length" x-cloak>
                <span class="fm-starters-label">{{ __('admin.forms.start_from') }}</span>
                <template x-for="starter in starters" :key="starter.key">
                    <button type="button" class="fm-starter" @click="applyStarter(starter)">
                        <i class="fas" :class="starter.icon"></i>
                        <span x-text="starter.title"></span>
                        <small x-text="starter.fields.length + ' ' + @js(__('admin.forms.fields_short'))"></small>
                    </button>
                </template>
            </div>

            {{-- ── Палитра: клик добавляет поле ПОСЛЕ выбранного ── --}}
            <div class="fm-palette">
                <span class="fm-pal-label">{{ __('admin.forms.add_field') }}</span>
                <div class="fm-pal-items">
                    <template x-for="(kind, key) in kinds" :key="key">
                        <button type="button" class="fm-chip" @click="addField(kind.variants[0])"
                                :title="kind.title + ' — ' + kind.desc">
                            <i class="fas" :class="kind.icon"></i>
                            <span x-text="kind.title"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- ── Список полей ── --}}
            <div class="fm-list-head">
                <span x-text="form.fields.length + ' ' + @js(__('admin.forms.fields_short'))"></span>
                <span class="fm-drag-hint" x-show="form.fields.length > 1"><i class="fas fa-up-down-left-right"></i> {{ __('admin.forms.drag_hint') }}</span>
            </div>

            <div class="fm-list" x-ref="list">
                <template x-for="(field, index) in form.fields" :key="field.uid">
                    <div class="fm-item" :class="{ 'is-open': opened === field.uid, 'is-picked': picked === field.uid }"
                         :data-uid="field.uid" @click="picked = field.uid">

                        <div class="fm-head">
                            <span class="fm-grip" :title="@js(__('admin.forms.move'))"><i class="fas fa-grip-vertical"></i></span>

                            <span class="fm-type-ico" :title="titleOf(field.type)"><i class="fas" :class="iconOf(field.type)"></i></span>

                            {{-- Подпись правится прямо здесь: разворачивать
                                 карточку ради переименования — лишний шаг. --}}
                            <input type="text" class="fm-inline"
                                   :name="'fields[' + index + '][label]'"
                                   x-model="field.label" maxlength="255"
                                   :placeholder="titleOf(field.type)"
                                   @focus="picked = field.uid">

                            <input type="hidden" :name="'fields[' + index + '][type]'" :value="field.type">
                            <input type="hidden" :name="'fields[' + index + '][name]'" :value="field.name">
                            {{-- Ширину не спрашиваем, а вычисляем по виду поля:
                                 короткий ответ встаёт в половину строки, длинный
                                 во всю. На сервер она всё равно уходит. --}}
                            <input type="hidden" :name="'fields[' + index + '][width]'" :value="widthOf(field.type)">

                            {{-- Обязательность — самая частая настройка, поэтому
                                 она кнопкой в строке, а не внутри карточки. --}}
                            <label class="fm-star" :class="{ 'is-on': field.required }"
                                   :title="@js(__('admin.forms.f_required'))"
                                   x-show="!isDecorative(field.type)">
                                <input type="checkbox" :name="'fields[' + index + '][required]'" value="1" x-model="field.required">
                                <i class="fas fa-asterisk"></i>
                            </label>

                            <button type="button" class="fm-icon-btn" :class="{ 'is-on': opened === field.uid }"
                                    @click.stop="opened = opened === field.uid ? null : field.uid"
                                    :title="@js(__('admin.forms.tune'))">
                                <i class="fas fa-sliders"></i>
                            </button>
                            <button type="button" class="fm-icon-btn" @click.stop="duplicateField(index)"
                                    :title="@js(__('admin.forms.act_duplicate'))">
                                <i class="fa-regular fa-clone"></i>
                            </button>
                            <button type="button" class="fm-icon-btn fm-icon-btn--danger" @click.stop="removeField(index)"
                                    :title="@js(__('admin.forms.act_delete'))">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>

                        {{-- Раскрытые настройки поля --}}
                        <div class="fm-body" x-show="opened === field.uid" x-cloak @click.stop>
                            {{-- Кнопки вставляют разметку в подпись или в пояснение —
                                 в то поле, где последний раз стоял курсор. Раньше
                                 здесь был список примеров текстом, и он повторял то,
                                 что и так написано в пояснении к типу. Показывать
                                 приём и объяснять его словами дважды — лишнее. --}}
                            <div class="fm-fmt-bar">
                                <span class="fm-fmt-label">{{ __('admin.forms.fmt_title') }}</span>
                                <button type="button" class="fm-fmt-btn" @mousedown.prevent
                                        @click="wrap($el, '**', '**', @js(__('admin.forms.fmt_bold')))"
                                        :title="@js(__('admin.forms.fmt_bold'))"><b>Ж</b></button>
                                <button type="button" class="fm-fmt-btn" @mousedown.prevent
                                        @click="wrap($el, '//', '//', @js(__('admin.forms.fmt_italic')))"
                                        :title="@js(__('admin.forms.fmt_italic'))"><i>К</i></button>
                                <button type="button" class="fm-fmt-btn" @mousedown.prevent
                                        @click="insertLink($el)"
                                        :title="@js(__('admin.forms.fmt_link_title'))"><i class="fas fa-link"></i></button>
                                <button type="button" class="fm-fmt-btn" @mousedown.prevent
                                        @click="wrap($el, '{#c62828|', '}', @js(__('admin.forms.fmt_color')))"
                                        :title="@js(__('admin.forms.fmt_color'))"><i class="fas fa-palette"></i></button>
                            </div>

                            {{-- Что этот тип вообще делает. Без пояснения «Ссылка»
                                 читается как кликабельное слово, хотя это поле
                                 ВВОДА адреса. --}}
                            <p class="fm-type-desc"><i class="fas fa-circle-info"></i> <span x-text="descOf(field.type)"></span></p>

                            <div class="fm-grid">
                                {{-- Уточнение вида. Показывается только там, где
                                     есть из чего выбирать: у файла или согласия
                                     список из одного пункта — это шум. --}}
                                <label class="fm-field" x-show="variantsOf(field.type).length > 1">
                                    <span class="fm-label">{{ __('admin.forms.f_variant') }}</span>
                                    <select x-model="field.type" @change="onTypeChange(field)" class="fm-input">
                                        <template x-for="v in variantsOf(field.type)" :key="v">
                                            <option :value="v" x-text="titleOf(v)"></option>
                                        </template>
                                    </select>
                                </label>

                                <label class="fm-field" x-show="!isDecorative(field.type) && field.type !== 'hidden'">
                                    <span class="fm-label">{{ __('admin.forms.f_placeholder') }}</span>
                                    <input type="text" :name="'fields[' + index + '][placeholder]'"
                                           x-model="field.placeholder" maxlength="255" class="fm-input"
                                           placeholder="{{ __('admin.forms.f_placeholder_ph') }}">
                                    <small class="fm-hint">{{ __('admin.forms.f_placeholder_hint') }}</small>
                                </label>

                                <label class="fm-field" x-show="!isDecorative(field.type)">
                                    <span class="fm-label">{{ __('admin.forms.f_hint') }}</span>
                                    <input type="text" :name="'fields[' + index + '][hint]'"
                                           x-model="field.hint" maxlength="255" class="fm-input"
                                           placeholder="{{ __('admin.forms.f_hint_ph') }}">
                                    <small class="fm-hint">{{ __('admin.forms.f_hint_hint') }}</small>
                                </label>

                                <label class="fm-field" x-show="field.type === 'hidden'" x-cloak>
                                    <span class="fm-label">{{ __('admin.forms.f_value') }}</span>
                                    <input type="text" :name="'fields[' + index + '][value]'"
                                           x-model="field.value" maxlength="255" class="fm-input"
                                           placeholder="{{ __('admin.forms.f_value_ph') }}">
                                </label>

                                {{-- Иконка поля. Выбирается из готового набора, а
                                     не вписывается руками: имя класса уходит в
                                     атрибут, и произвольная строка там ни к чему. --}}
                                <div class="fm-field" x-data="{ open: false }" @click.outside="open = false">
                                    <span class="fm-label">{{ __('admin.forms.f_icon') }}</span>
                                    <input type="hidden" :name="'fields[' + index + '][icon]'" :value="field.icon">

                                    <div class="fm-picker">
                                        <button type="button" class="fm-picker-btn" @click="open = !open">
                                            <i class="fas" :class="field.icon ? field.icon.replace('fas ', '') : 'fa-ban'"></i>
                                            <span x-text="field.icon ? field.icon.replace('fas fa-', '') : @js(__('admin.forms.icon_none'))"></span>
                                            <i class="fas fa-chevron-down fm-picker-arrow"></i>
                                        </button>

                                        <div class="fm-picker-drop" x-show="open" x-cloak>
                                            <button type="button" class="fm-ico-btn" :class="{ 'is-on': !field.icon }"
                                                    @click="field.icon = ''; open = false" :title="@js(__('admin.forms.icon_none'))">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            <template x-for="name in icons" :key="name">
                                                <button type="button" class="fm-ico-btn" :class="{ 'is-on': field.icon === 'fas ' + name }"
                                                        @click="field.icon = 'fas ' + name; open = false">
                                                    <i class="fas" :class="name"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Варианты --}}
                            <div class="mt-3" x-show="hasOptions(field.type)" x-cloak>
                                <span class="fm-label">{{ __('admin.forms.f_options') }}</span>

                                <div class="fm-options">
                                    <template x-for="(option, oi) in field.options" :key="oi">
                                        <div class="fm-option">
                                            <input type="text" :name="'fields[' + index + '][options][]'"
                                                   x-model="field.options[oi]" maxlength="255" class="fm-input"
                                                   @keydown.enter.prevent="addOption(field, oi)">
                                            <button type="button" class="fm-icon-btn fm-icon-btn--danger"
                                                    @click="field.options.splice(oi, 1)">
                                                <i class="fas fa-xmark"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <div class="flex items-center gap-2 mt-2">
                                    <button type="button" class="fm-add-opt" @click="addOption(field)">
                                        <i class="fas fa-plus"></i> {{ __('admin.forms.add_option') }}
                                    </button>
                                    {{-- Вставка списком: набивать варианты по одному
                                         долго, а они почти всегда уже есть текстом. --}}
                                    <button type="button" class="fm-add-opt" @click="pasteOptions(field)">
                                        <i class="fa-regular fa-paste"></i> {{ __('admin.forms.paste_options') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Обязательность повторена галочкой. Звёздочка в
                                 строке — быстрый способ для тех, кто её уже
                                 нашёл, но опираться только на неё нельзя:
                                 настройка называется словами, и искать её будут
                                 здесь, среди остальных. --}}
                            <label class="fm-switch" style="margin-top:10px" x-show="!isDecorative(field.type)">
                                <span class="admin-toggle">
                                    <input type="checkbox" x-model="field.required">
                                    <span class="track"></span><span class="knob"></span>
                                </span>
                                <span>{{ __('admin.forms.f_required') }}</span>
                            </label>

                            <p class="fm-field-hint" x-show="field.name" x-cloak>
                                {{ __('admin.forms.field_name_is') }} <code x-text="field.name"></code>
                            </p>
                        </div>
                    </div>
                </template>
            </div>

            <p class="fm-empty" x-show="!form.fields.length" x-cloak>{{ __('admin.forms.no_fields') }}</p>

            {{-- ── Настройки ── --}}
            <h3 class="fm-section">{{ __('admin.forms.settings') }}</h3>

            <div class="fm-grid">
                <label class="fm-field">
                    <span class="fm-label">{{ __('admin.forms.s_submit') }}</span>
                    <input type="text" name="settings[submit_label]" x-model="form.settings.submit_label"
                           maxlength="64" class="fm-input" placeholder="{{ __('forms.submit') }}">
                </label>

                <label class="fm-field">
                    <span class="fm-label">{{ __('admin.forms.s_notify') }}</span>
                    <input type="text" name="settings[notify_email]" x-model="form.settings.notify_email"
                           maxlength="255" class="fm-input" placeholder="mail@example.com">
                </label>

                <label class="fm-field fm-field--wide">
                    <span class="fm-label">{{ __('admin.forms.s_success') }}</span>
                    <input type="text" name="settings[success_message]" x-model="form.settings.success_message"
                           maxlength="500" class="fm-input" placeholder="{{ __('forms.sent') }}">
                </label>
            </div>

            <div class="fm-switches">
                <label class="fm-switch">
                    <span class="admin-toggle">
                        <input type="checkbox" name="settings[columns]" value="1" x-model="form.settings.columns">
                        <span class="track"></span><span class="knob"></span>
                    </span>
                    <span>{{ __('admin.forms.s_columns') }}</span>
                </label>
                <label class="fm-switch">
                    <span class="admin-toggle">
                        <input type="checkbox" name="settings[show_title]" value="1" x-model="form.settings.show_title">
                        <span class="track"></span><span class="knob"></span>
                    </span>
                    <span>{{ __('admin.forms.s_show_title') }}</span>
                </label>
                <label class="fm-switch">
                    <span class="admin-toggle">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active">
                        <span class="track"></span><span class="knob"></span>
                    </span>
                    <span>{{ __('admin.forms.s_active') }}</span>
                </label>
            </div>

            {{-- Редкое — под кнопкой: иначе семь одинаково заметных полей,
                 из которых пять не трогают никогда. --}}
            <button type="button" class="fm-more" @click="advanced = !advanced">
                <i class="fas" :class="advanced ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                {{ __('admin.forms.advanced') }}
            </button>

            <div class="fm-grid mt-2" x-show="advanced" x-cloak>
                <label class="fm-field">
                    <span class="fm-label">{{ __('admin.forms.s_redirect') }}</span>
                    <input type="text" name="settings[redirect_url]" x-model="form.settings.redirect_url"
                           maxlength="255" class="fm-input" placeholder="/spasibo">
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

                <label class="fm-field fm-field--wide">
                    <span class="fm-label">{{ __('admin.forms.s_note') }}</span>
                    <input type="text" name="settings[note]" x-model="form.settings.note" maxlength="255" class="fm-input">
                </label>
            </div>

            <div class="fm-actions">
                <button type="submit" class="fm-btn" :disabled="submitting || !form.fields.length">
                    <i class="fas fa-floppy-disk"></i>
                    <span x-text="editing ? @js(__('admin.forms.save')) : @js(__('admin.forms.create'))"></span>
                </button>
                <button type="button" class="fm-btn fm-btn--ghost" @click="reset()" x-show="editing" x-cloak>
                    {{ __('admin.cancel') }}
                </button>
                <span class="fm-hint" x-show="!form.fields.length" x-cloak>{{ __('admin.forms.need_field') }}</span>
            </div>
        </section>

    </form>

        {{-- Превью стоит СНАРУЖИ формы конструктора, хотя визуально это её
             вторая колонка. Причина: вложенные формы запрещены разметкой, и
             браузер молча выбрасывает внутренний тег form — вместе с ним
             терялась сетка .rf-form, и превью показывало поля в одну колонку
             там, где на сайте их две. Раскладку держит .fm-layout на общей
             обёртке. --}}
        <aside class="fm-side">
            <div class="admin-card p-4">
                <h2 class="fm-section fm-section--first">{{ __('admin.forms.preview') }}</h2>

                {{-- Клик по полю в превью раскрывает его карточку слева: на
                     длинной форме искать нужное поле глазами по двум колонкам
                     утомительно. Номер поля приходит атрибутом data-fi. --}}
                <div class="fm-preview" @click="pickFromPreview($event)">
                    <div x-show="loading" class="fm-hint">{{ __('admin.forms.building_preview') }}</div>
                    <div x-show="!loading" x-html="preview"></div>
                </div>

                <p class="fm-note">{{ __('admin.forms.preview_note') }}</p>
                <div x-cloak x-show="error" class="admin-note p-3 mt-3 text-xs" x-text="error"></div>
            </div>
        </aside>

    </div>

    {{-- ══════════ Сохранённые формы ══════════ --}}
    <section class="admin-card p-4 mt-4">
        <h2 class="fm-section fm-section--first">{{ __('admin.forms.saved') }}</h2>

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
                        {{-- Подтверждение показывает сама кнопка: обработчик
                             живёт в лейауте панели, один на все разделы. --}}
                        <button type="button" class="fm-copy"
                                data-copy="{{ $item->shortcode() }}"
                                data-copied="{{ __('admin.forms.copied') }}">
                            <i class="fa-regular fa-copy"></i>
                            <code>{{ $item->shortcode() }}</code>
                        </button>

                        <button type="button" class="fm-copy"
                                data-copy="{{ $item->bladeSnippet() }}"
                                data-copied="{{ __('admin.forms.copied') }}">
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

    {{-- ══════════ Памятка ══════════ --}}
    <section class="admin-card p-4 mt-4">
        <h2 class="fm-section fm-section--first">{{ __('admin.forms.howto') }}</h2>

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

            <div class="md:col-span-2">
                <h3 class="fm-h3">{{ __('admin.forms.h5') }}</h3>
                <p class="fm-p">{{ __('admin.forms.h5_text') }}</p>
                <pre class="fm-pre"><code>Согласен с [политикой конфиденциальности](/privacy)
Пишите на [почту](mailto:mail@example.com) или [позвоните](tel:+79000000000)
**Важно:** приложите //скан// договора
Поле {#c62828|обязательно} к заполнению</code></pre>
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
{{-- Оформление форм САЙТА нужно и здесь: превью приезжает по AJAX и
     вставляется в готовую страницу, поэтому @push('styles') из самой вьюхи
     формы сюда не доходит — стека уже нет. Без этой строки превью
     показывалось голой лентой текста, включая скрытое поле-ловушку, которое
     прячется как раз стилем. Классы там все с приставкой rf-, в панель
     ничего не протекает. --}}
<link rel="stylesheet" href="{{ asset_v('assets/css/forms.css') }}">
<style>
    /* Литеральный CSS: в сборке проекта нет opacity-модификаторов,
       произвольных значений и половины цветов v3 (см. CLAUDE.md). */

    .fm-layout { display:grid; gap:16px; align-items:start }
    @media (min-width:1280px) { .fm-layout { grid-template-columns:minmax(0,1fr) 24rem } }

    /* Превью не должно уезжать вверх, когда список полей длинный. */
    .fm-side { min-width:0 }
    @media (min-width:1280px) { .fm-side { position:sticky; top:84px } }

    .fm-top { display:grid; gap:12px; margin-bottom:14px }
    @media (min-width:640px) { .fm-top { grid-template-columns:1fr 1fr } }

    .fm-grid { display:grid; gap:12px }
    @media (min-width:640px) { .fm-grid { grid-template-columns:1fr 1fr } }
    .fm-field--wide { grid-column:1 / -1 }

    .fm-field { display:flex; flex-direction:column; gap:4px; min-width:0 }
    .fm-label { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#6b7280 }
    .fm-hint  { font-size:.72rem; color:#9ca3af; line-height:1.45 }
    .fm-input { width:100%; padding:7px 10px; font-size:.85rem; color:#111827; background:#fff;
                border:1px solid #d1d5db; outline:none; transition:border-color .15s ease }
    .fm-input:focus { border-color:#6366f1 }
    .fm-input--big { font-size:.95rem; font-weight:600; padding:9px 11px }

    /* ── Быстрый старт ── */
    .fm-starters { display:flex; flex-wrap:wrap; align-items:stretch; gap:8px; margin-bottom:14px;
                   padding:12px; background:#f8f9ff; border:1px dashed #c7cbf5 }
    .fm-starters-label { width:100%; font-size:.72rem; font-weight:700; text-transform:uppercase;
                         letter-spacing:.04em; color:#6366f1 }
    .fm-starter { display:flex; flex-direction:column; align-items:flex-start; gap:2px; padding:8px 12px;
                  font-size:.82rem; font-weight:600; color:#312e81; background:#fff;
                  border:1px solid #c7cbf5; cursor:pointer; transition:border-color .15s ease }
    .fm-starter:hover { border-color:#6366f1 }
    .fm-starter small { font-size:.68rem; font-weight:400; color:#8b90a8 }

    /* ── Палитра ── */
    .fm-palette { display:grid; gap:8px; padding:10px; margin-bottom:12px; background:#fafafa;
                  border:1px solid #eceef3 }
    .fm-pal-group { display:flex; flex-wrap:wrap; align-items:center; gap:6px }
    .fm-pal-label { width:5.6rem; flex:0 0 auto; font-size:.68rem; font-weight:700; text-transform:uppercase;
                    letter-spacing:.04em; color:#9ca3af }
    .fm-pal-items { display:flex; flex-wrap:wrap; gap:5px; flex:1 1 auto; min-width:0 }
    .fm-chip { display:inline-flex; align-items:center; gap:5px; padding:4px 9px; font-size:.75rem;
               color:#374151; background:#fff; border:1px solid #e1e4ea; cursor:pointer;
               transition:border-color .15s ease, color .15s ease }
    .fm-chip:hover { border-color:#6366f1; color:#4338ca }

    /* ── Список полей ── */
    .fm-list-head { display:flex; align-items:center; justify-content:space-between; gap:10px;
                    margin-bottom:6px; font-size:.72rem; font-weight:700; text-transform:uppercase;
                    letter-spacing:.04em; color:#9ca3af }
    .fm-drag-hint { display:inline-flex; align-items:center; gap:5px; font-weight:400; text-transform:none;
                    letter-spacing:0; font-size:.72rem }

    .fm-list { display:flex; flex-direction:column; gap:5px }
    .fm-item { border:1px solid #e5e7eb; background:#fff }
    .fm-item.is-picked { border-color:#c7cbf5 }
    .fm-item.is-open { border-color:#6366f1 }

    .fm-head { display:flex; align-items:center; gap:7px; padding:6px 8px }
    .fm-grip { display:inline-flex; align-items:center; justify-content:center; width:18px; height:26px;
               color:#c3c7d1; cursor:grab; flex:0 0 auto }
    .fm-grip:active { cursor:grabbing }
    .fm-type-ico { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
                   font-size:12px; color:#4f46e5; background:#eef2ff; flex:0 0 auto }

    /* Подпись правится прямо в строке: поле без рамки, пока в него не зашли. */
    .fm-inline { flex:1 1 auto; min-width:0; padding:5px 7px; font-size:.86rem; font-weight:600;
                 color:#111827; background:transparent; border:1px solid transparent; outline:none;
                 transition:border-color .15s ease, background .15s ease }
    .fm-inline:hover { border-color:#e5e7eb }
    .fm-inline:focus { border-color:#6366f1; background:#fff }
    .fm-inline::placeholder { font-weight:400; color:#b6bac4 }

    .fm-star { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
               font-size:9px; color:#c3c7d1; border:1px solid #eceef3; cursor:pointer; flex:0 0 auto }
    .fm-star input { position:absolute; opacity:0; width:0; height:0 }
    .fm-star.is-on { color:#dc2626; border-color:#fecaca; background:#fef2f2 }

    .fm-icon-btn { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
                   font-size:11px; color:#6b7280; background:#fff; border:1px solid #eceef3; cursor:pointer;
                   flex:0 0 auto; transition:background .15s ease, color .15s ease }
    .fm-icon-btn:hover { background:#f3f4f6; color:#111827 }
    .fm-icon-btn.is-on { color:#4338ca; border-color:#c7cbf5; background:#eef2ff }
    .fm-icon-btn--danger:hover { background:#dc2626; border-color:#dc2626; color:#fff }

    .fm-body { padding:10px; border-top:1px solid #eceef3; background:#fafbfc }
    .fm-field-hint { margin-top:8px; font-size:.7rem; color:#9ca3af }

    /* Пояснение к типу поля. */
    .fm-type-desc { display:flex; align-items:flex-start; gap:6px; margin:0 0 10px; padding:7px 9px;
                    font-size:.75rem; line-height:1.45; color:#3730a3; background:#eef2ff }

    /* Выбор иконки: кнопка как обычное поле, набор — выпадающим окном.
       Лента из тридцати кнопок занимала пол-экрана и спорила по весу с самими
       полями формы. */
    .fm-picker { position:relative }
    .fm-picker-btn { display:flex; align-items:center; gap:8px; width:100%; padding:7px 10px;
                     font-size:.85rem; color:#111827; background:#fff; border:1px solid #d1d5db;
                     cursor:pointer; text-align:left }
    .fm-picker-btn:hover { border-color:#6366f1 }
    .fm-picker-btn > .fas:first-child { width:16px; color:#4f46e5; text-align:center }
    .fm-picker-btn span { flex:1 1 auto; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .fm-picker-arrow { font-size:9px; color:#9ca3af }

    .fm-picker-drop { position:absolute; z-index:30; top:calc(100% + 3px); left:0; right:0;
                      display:grid; grid-template-columns:repeat(auto-fill, minmax(32px, 1fr)); gap:4px;
                      max-height:11rem; overflow-y:auto; padding:6px;
                      background:#fff; border:1px solid #d1d5db; box-shadow:0 10px 26px rgba(17,24,39,.12) }
    .fm-ico-btn { display:inline-flex; align-items:center; justify-content:center; height:30px;
                  font-size:12px; color:#6b7280; background:#fff; border:1px solid #eceef3; cursor:pointer;
                  transition:border-color .15s ease, color .15s ease }
    .fm-ico-btn:hover { color:#4338ca; border-color:#c7cbf5 }
    .fm-ico-btn.is-on { color:#fff; background:#4f46e5; border-color:#4f46e5 }

    /* Тумблеры — те же, что в Каптче (.admin-toggle из лейаута панели):
       разделы не должны отличаться друг от друга оформлением одной и той же
       настройки. */
    .fm-switch { display:flex; align-items:center; gap:10px; padding:8px 10px;
                 font-size:.83rem; color:#374151; background:#fff; border:1px solid #eceef3; cursor:pointer }
    .fm-switch:hover { border-color:#c7cbf5 }
    .fm-switches { display:grid; gap:6px; margin-top:10px }
    @media (min-width:640px) { .fm-switches { grid-template-columns:repeat(3, minmax(0,1fr)) } }

    /* Панель оформления подписи: кнопки, а не список примеров текстом.
       Текстовая памятка объясняла ровно то же, что пояснение к типу поля, —
       одно и то же двумя способами. Кнопка показывает приём делом. */
    .fm-fmt-bar { display:flex; flex-wrap:wrap; align-items:center; gap:5px; margin-bottom:10px;
                  padding:6px 8px; background:#fff; border:1px solid #eceef3 }
    .fm-fmt-label { margin-right:2px; font-size:.7rem; font-weight:700; text-transform:uppercase;
                    letter-spacing:.04em; color:#9ca3af }
    .fm-fmt-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:26px;
                  font-size:12px; color:#4b5563; background:#fff; border:1px solid #e5e7eb; cursor:pointer;
                  transition:border-color .15s ease, color .15s ease }
    .fm-fmt-btn:hover { color:#4338ca; border-color:#c7cbf5 }
    .fm-field-hint code { padding:1px 5px; color:#4b5563; background:#eceef3 }

    .fm-options { display:flex; flex-direction:column; gap:5px; margin-top:5px }
    .fm-option { display:flex; align-items:center; gap:6px }
    .fm-add-opt { display:inline-flex; align-items:center; gap:5px; padding:4px 9px; font-size:.74rem;
                  color:#4338ca; background:#eef2ff; border:0; cursor:pointer }

    .fm-empty { padding:16px; font-size:.82rem; color:#9ca3af; text-align:center;
                border:1px dashed #e1e4ea }

    /* Односложные классы: SortableJS применяет их через classList.add(), а тот
       по спецификации падает с InvalidCharacterError на пробеле в токене. */
    .fm-ghost { opacity:.35 }
    .fm-chosen { border-color:#6366f1 }
    .fm-drag { box-shadow:0 8px 20px rgba(17,24,39,.14) }

    .fm-section { margin:18px 0 8px; font-size:.72rem; font-weight:700; text-transform:uppercase;
                  letter-spacing:.06em; color:#9ca3af }
    .fm-section--first { margin-top:0 }

    .fm-check { display:inline-flex; align-items:center; gap:7px; font-size:.82rem; color:#374151; cursor:pointer }
    .fm-check input { width:15px; height:15px; accent-color:#6366f1 }

    .fm-more { display:inline-flex; align-items:center; gap:7px; margin-top:12px; padding:5px 0;
               font-size:.78rem; color:#4338ca; background:none; border:0; cursor:pointer }

    .fm-actions { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-top:16px;
                  padding-top:14px; border-top:1px solid #eceef3 }
    .fm-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; font-size:.85rem;
              font-weight:600; color:#fff; background:#4f46e5; border:0; cursor:pointer;
              transition:background .15s ease }
    .fm-btn:hover { background:#4338ca }
    .fm-btn[disabled] { opacity:.5; cursor:default }
    .fm-btn--ghost { color:#4b5563; background:#fff; border:1px solid #d1d5db }
    .fm-btn--ghost:hover { background:#f3f4f6 }

    /* Превью показывает форму ровно в том оформлении, что и сайт (forms.css
       подключён выше). Светлая подложка отделяет «страницу сайта» от панели,
       иначе поля формы сливаются с полями конструктора. */
    .fm-preview { padding:16px; border:1px solid #e5e7eb; background:#fbfbfd; min-height:110px;
                  box-shadow:inset 0 1px 3px rgba(17,24,39,.05) }
    .fm-preview .rf { margin:0 }
    .fm-note { margin-top:8px; font-size:.72rem; color:#9ca3af; line-height:1.5 }

    .fm-saved { display:flex; align-items:flex-start; gap:12px; padding:11px 0; border-top:1px solid #f3f4f6 }
    .fm-saved:first-of-type { border-top:0 }
    .fm-code { padding:1px 6px; font-size:.68rem; color:#4b5563; background:#f3f4f6 }
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
<script src="{{ local_js('sortable.min.js') }}"></script>
<script>
    /**
     * Конструктор форм.
     *
     * Превью строит СЕРВЕР тем же сервисом, что выводит форму на сайте:
     * рисовать её второй раз на клиенте значило бы завести вторую разметку,
     * которая неизбежно разойдётся с настоящей.
     */
    function formBuilder(blank, meta, kinds, halfWidth, captchas, starters) {
        return {
            blank: blank,
            meta: meta,
            kinds: kinds,
            halfWidth: halfWidth,
            captchas: captchas,
            starters: starters,
            form: null,
            editing: null,
            opened: null,
            picked: null,
            advanced: false,
            preview: '',
            loading: false,
            error: '',
            submitting: false,
            timer: null,
            requestId: 0,

            init() {
                this.form = this.prepare(this.blank);
                this.refresh();
                this.$watch('form', () => this.schedule(), { deep: true });
                this.$nextTick(() => this.initSortable());
            },

            /**
             * Перетаскивание списка полей.
             *
             * Порядок хранит Alpine, а DOM двигает Sortable — поэтому его
             * перестановку сразу отменяем и меняем массив: иначе два хозяина
             * одного списка расходятся, и следующая перерисовка возвращает
             * поле на место.
             */
            initSortable() {
                if (!window.Sortable || !this.$refs.list) {
                    return;
                }

                var self = this;

                window.Sortable.create(this.$refs.list, {
                    handle: '.fm-grip',
                    animation: 150,
                    // Ровно ОДИН токен на класс: Sortable применяет их через
                    // classList.add(), а тот падает с InvalidCharacterError на
                    // пробеле (см. CLAUDE.md про модуль Меню).
                    ghostClass: 'fm-ghost',
                    chosenClass: 'fm-chosen',
                    dragClass: 'fm-drag',
                    onEnd: function (event) {
                        var from = event.oldIndex;
                        var to = event.newIndex;

                        if (from === to) {
                            return;
                        }

                        event.item.remove();
                        event.from.insertBefore(event.item, event.from.children[from] || null);

                        var moved = self.form.fields.splice(from, 1)[0];
                        self.form.fields.splice(to, 0, moved);
                    }
                });
            },

            /** Каждому полю нужен устойчивый ключ для x-for. */
            prepare(source) {
                var form = JSON.parse(JSON.stringify(source));

                form.fields = (form.fields || []).map((field) => Object.assign({
                    icon: '', placeholder: '', hint: '', value: '', required: false, width: 'full', options: []
                }, field, { uid: this.uid() }));

                form.settings = Object.assign({
                    submit_label: '', success_message: '', note: '',
                    notify_email: '', redirect_url: '', captcha: '', icon: '',
                    columns: true, show_title: true
                }, form.settings || {});

                return form;
            },

            uid() {
                return 'f' + Math.random().toString(36).slice(2, 9);
            },

            /**
             * Набор иконок. Ограниченный список, а не поле ввода: имя класса
             * уходит прямо в атрибут, и произвольная строка там ни к чему.
             * Взяты те, что реально нужны формам, — контакты, документы,
             * заказы, время.
             */
            icons: [
                'fa-user', 'fa-at', 'fa-phone', 'fa-mobile-screen', 'fa-comment', 'fa-comments',
                'fa-envelope', 'fa-building', 'fa-briefcase', 'fa-id-card', 'fa-file-lines',
                'fa-paperclip', 'fa-calendar-days', 'fa-clock', 'fa-location-dot', 'fa-map',
                'fa-cart-shopping', 'fa-tag', 'fa-ruble-sign', 'fa-truck', 'fa-star',
                'fa-circle-question', 'fa-circle-info', 'fa-shield-halved', 'fa-lock',
                'fa-heart', 'fa-wrench', 'fa-stethoscope', 'fa-graduation-cap', 'fa-gift'
            ],

            /**
             * Поле, куда вставлять разметку.
             *
             * Ищем в DOM, а не храним ссылку в состоянии: Alpine оборачивает
             * реактивные значения в Proxy, и DOM-узел, положенный туда,
             * перестаёт быть самим собой — сравнение с живым элементом даёт
             * false, а обращение к selectionStart работает не так, как ждёшь.
             *
             * Кнопки гасят mousedown, поэтому фокус остаётся на поле и его
             * видно через activeElement. Если фокуса нет — берём подпись
             * карточки: чаще всего оформляют именно её.
             */
            fmtInput(button) {
                var card = button.closest('.fm-item');

                if (!card) {
                    return null;
                }

                var active = document.activeElement;

                if (active && card.contains(active) && typeof active.selectionStart === 'number') {
                    return active;
                }

                return card.querySelector('.fm-inline');
            },

            /** Обернуть выделенное (или подставить пример, если ничего не выделено). */
            wrap(button, before, after, sample) {
                var input = this.fmtInput(button);

                if (!input) {
                    return;
                }

                var start = input.selectionStart;
                var end = input.selectionEnd;
                var value = input.value;
                var chosen = value.slice(start, end) || sample;

                input.value = value.slice(0, start) + before + chosen + after + value.slice(end);

                // Alpine следит за x-model, а программная правка value события
                // не порождает — отправляем его сами, иначе изменение не
                // доедет ни до сборки, ни до превью.
                input.dispatchEvent(new Event('input'));
                input.focus();
                input.setSelectionRange(start + before.length, start + before.length + chosen.length);
            },

            /** Ссылка: адрес спрашиваем, текст берём из выделения. */
            insertLink(button) {
                var href = window.prompt(@js(__('admin.forms.fmt_link_prompt')), '/privacy');

                if (!href) {
                    return;
                }

                this.wrap(button, '[', '](' + href.trim() + ')', @js(__('admin.forms.fmt_link_text')));
            },

            /** Уточнения того вида, к которому относится тип. */
            variantsOf(type) {
                for (var key in this.kinds) {
                    if (this.kinds[key].variants.indexOf(type) !== -1) {
                        return this.kinds[key].variants;
                    }
                }

                return [type];
            },

            /**
             * Ширина по виду поля: телефон, дата и число рядом читаются лучше,
             * чем растянутые на всю строку, а сообщение или список вариантов —
             * наоборот. Отдельной настройки для этого больше нет.
             */
            widthOf(type) {
                return this.halfWidth.indexOf(type) !== -1 ? 'half' : 'full';
            },

            /**
             * Открыть карточку поля, по которому щёлкнули в превью.
             *
             * Внутри превью живёт настоящая форма — щелчок по её кнопке или
             * ссылке не должен ничего отправлять и никуда уводить.
             */
            pickFromPreview(event) {
                if (event.target.closest('a, button')) {
                    event.preventDefault();
                }

                var row = event.target.closest('[data-fi]');

                if (!row) {
                    return;
                }

                var field = this.form.fields[Number(row.dataset.fi)];

                if (!field) {
                    return;
                }

                this.picked = field.uid;
                this.opened = field.uid;

                this.$nextTick(() => {
                    var card = this.$refs.list.querySelector('[data-uid="' + field.uid + '"]');

                    if (card) {
                        card.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }
                });
            },

            titleOf(type) {
                return this.meta[type] ? this.meta[type].title : type;
            },

            descOf(type) {
                return this.meta[type] ? this.meta[type].desc : '';
            },

            iconOf(type) {
                return this.meta[type] ? this.meta[type].icon : 'fa-font';
            },

            hasOptions(type) {
                return ['select', 'radio', 'checkboxes'].indexOf(type) !== -1;
            },

            isDecorative(type) {
                return ['heading', 'paragraph'].indexOf(type) !== -1;
            },

            /**
             * Новое поле встаёт ПОСЛЕ выбранного, а не в конец списка: собирая
             * форму, чаще дописывают рядом с тем, на что смотрят.
             */
            addField(type) {
                var field = {
                    uid: this.uid(), type: type, name: '', label: '', icon: '',
                    placeholder: '', hint: '', value: '', required: false,
                    width: this.widthOf(type),
                    options: this.hasOptions(type) ? [@js(__('admin.forms.option_example')) + ' 1', @js(__('admin.forms.option_example')) + ' 2'] : []
                };

                var at = this.indexOf(this.picked);
                var position = at === -1 ? this.form.fields.length : at + 1;

                this.form.fields.splice(position, 0, field);
                this.picked = field.uid;

                // Разметке настраивать нечего, у остальных сразу открываем
                // подпись — это первое, что заполняют.
                this.$nextTick(() => {
                    var node = this.$refs.list.querySelector('[data-uid="' + field.uid + '"] .fm-inline');
                    if (node) {
                        node.focus();
                    }
                });
            },

            indexOf(uid) {
                return this.form.fields.findIndex((field) => field.uid === uid);
            },

            duplicateField(index) {
                var copy = JSON.parse(JSON.stringify(this.form.fields[index]));
                copy.uid = this.uid();
                // Имя очищаем: два поля с одним именем затирали бы друг друга
                // в заявке, и второй ответ просто не сохранился бы.
                copy.name = '';
                this.form.fields.splice(index + 1, 0, copy);
                this.picked = copy.uid;
            },

            removeField(index) {
                var removed = this.form.fields.splice(index, 1)[0];

                if (this.opened === removed.uid) {
                    this.opened = null;
                }

                if (this.picked === removed.uid) {
                    this.picked = null;
                }
            },

            addOption(field, after) {
                var position = typeof after === 'number' ? after + 1 : field.options.length;
                field.options.splice(position, 0, '');
            },

            /** Варианты почти всегда уже есть готовым списком — по строке на пункт. */
            pasteOptions(field) {
                var text = window.prompt(@js(__('admin.forms.paste_prompt')), '');

                if (!text) {
                    return;
                }

                var lines = text.split(/[\n;]+/).map((line) => line.trim()).filter(Boolean);

                if (lines.length) {
                    field.options = lines;
                }
            },

            /** Смена типа: варианты нужны не всем, разметке — не нужны вовсе. */
            onTypeChange(field) {
                field.width = this.widthOf(field.type);

                if (this.hasOptions(field.type) && !field.options.length) {
                    field.options = [@js(__('admin.forms.option_example')) + ' 1', @js(__('admin.forms.option_example')) + ' 2'];
                }

                if (!this.hasOptions(field.type)) {
                    field.options = [];
                }
            },

            /** Заготовка: пустая форма с нуля — самый долгий путь. */
            applyStarter(starter) {
                this.form.title = this.form.title || starter.title;
                this.form.fields = starter.fields.map((field) => Object.assign({
                    icon: '', placeholder: '', hint: '', value: '', required: false, width: 'full', options: []
                }, field, { uid: this.uid() }));
            },

            edit(item) {
                this.editing = item.id;
                this.form = this.prepare(item);
                this.opened = null;
                this.picked = null;
                this.refresh();
                this.$nextTick(() => this.initSortable());
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            reset() {
                this.editing = null;
                this.form = this.prepare(this.blank);
                this.opened = null;
                this.picked = null;
                this.refresh();
                this.$nextTick(() => this.initSortable());
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
