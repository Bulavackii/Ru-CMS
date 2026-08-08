{{--
    Вывод формы на сайте.

    Оформление — литеральный CSS с префиксом .rf-, а не Tailwind-утилиты:
    сборка public/assets/css/tailwind.min.css неполная (нет opacity-модификаторов,
    произвольных значений и половины цветов), и полагаться на неё в модуле,
    который вставляется в произвольный материал, нельзя. Подключается один раз
    на страницу, сколько бы форм на ней ни стояло.

    Форма работает БЕЗ JavaScript: обычная отправка POST, ответ приходит
    редиректом с флеш-сообщением. Скрипт только гасит повторное нажатие кнопки.
--}}
@php
    /** @var \Modules\Forms\Models\Form $form */
    $anchor  = 'form-' . $form->slug;
    $sent    = session('form_sent') === $form->slug;
    $errors  = session('form_errors_' . $form->slug);
    $old     = session('form_old_' . $form->slug, []);
    $columns = $form->setting('columns', true);
@endphp

<div class="rf" id="{{ $anchor }}">
    @if($form->title && $form->setting('show_title', true))
        <h3 class="rf-title">
            @if($form->icon())<i class="{{ $form->icon() }} rf-title-ico" aria-hidden="true"></i>@endif
            {{ $form->title }}
        </h3>
    @endif

    @if($form->description)
        {{-- Описание и подписи полей поддерживают простое оформление: жирный,
             курсив, цвет и ссылку. Текст сначала экранируется целиком, и
             только потом в него вносится разметка — см. FormService::format. --}}
        <p class="rf-desc">{!! \Modules\Forms\Services\FormService::format($form->description) !!}</p>
    @endif

    @if($sent)
        <div class="rf-done" role="status">
            <span class="rf-done-ico" aria-hidden="true">✓</span>
            <span>{{ $form->setting('success_message') ?: __('forms.sent') }}</span>
        </div>
    @endif

    @if($errors)
        <div class="rf-fail" role="alert">
            <strong>{{ __('forms.check_form') }}</strong>
            <ul>
                @foreach((array) $errors as $message)
                    <li>{{ is_array($message) ? reset($message) : $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('forms.submit', $form->slug) }}"
          @if($form->setting('has_upload')) enctype="multipart/form-data" @endif
          class="rf-form {{ $columns ? 'is-grid' : '' }}"
          novalidate>
        @csrf
        <input type="hidden" name="_return" value="{{ url()->current() }}#{{ $anchor }}">

        {{-- Ловушка для простых ботов: человек её не видит и не наводит на
             неё табуляцией, а заполняющий всё подряд скрипт — заполнит. --}}
        <div class="rf-trap" aria-hidden="true">
            <label>{{ __('forms.trap_label') }}
                <input type="text" name="{{ \Modules\Forms\Services\FormService::HONEYPOT }}"
                       tabindex="-1" autocomplete="off">
            </label>
        </div>

        @foreach($fields as $index => $field)
            @php
                $id    = $anchor . '-' . $field['name'];
                // Подпись с оформлением и иконкой — собирается один раз на поле.
                $label = \Modules\Forms\Services\FormService::format($field['label']);
                $ico   = $field['icon'] ? '<i class="' . $field['icon'] . ' rf-ico" aria-hidden="true"></i> ' : '';
                $value = data_get($old, $field['name'], $field['value']);
                $wide  = $field['width'] === 'full' || ! $columns;
            @endphp

            {{-- Номер поля выводится ТОЛЬКО в превью конструктора: по нему
                 клик открывает карточку этого поля слева. На сайте разметка
                 остаётся чистой — служебным атрибутам там делать нечего. --}}
            <div class="rf-row {{ $wide ? 'is-wide' : '' }}"
                 @if(! empty($options['preview'])) data-fi="{{ $index }}" @endif>
                @switch($field['type'])
                    @case('heading')
                        <h4 class="rf-h">{!! $ico !!}{!! $label !!}</h4>
                        @break

                    @case('paragraph')
                        <p class="rf-p">{!! $ico !!}{!! $label !!}</p>
                        @break

                    @case('hidden')
                        <input type="hidden" name="fields[{{ $field['name'] }}]" value="{{ $value }}">
                        @break

                    @case('textarea')
                        <label class="rf-label" for="{{ $id }}">
{!! $ico !!}{!! $label !!}@if($field['required'])<span class="rf-req">*</span>@endif
                        </label>
                        <textarea id="{{ $id }}" name="fields[{{ $field['name'] }}]" rows="5"
                                  class="rf-input" placeholder="{{ $field['placeholder'] }}"
                                  @if($field['required']) required @endif>{{ $value }}</textarea>
                        @break

                    @case('select')
                        <label class="rf-label" for="{{ $id }}">
{!! $ico !!}{!! $label !!}@if($field['required'])<span class="rf-req">*</span>@endif
                        </label>
                        <select id="{{ $id }}" name="fields[{{ $field['name'] }}]" class="rf-input"
                                @if($field['required']) required @endif>
                            <option value="">{{ $field['placeholder'] ?: __('forms.choose') }}</option>
                            @foreach($field['options'] as $option)
                                <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @break

                    @case('radio')
                        <span class="rf-label">
{!! $ico !!}{!! $label !!}@if($field['required'])<span class="rf-req">*</span>@endif
                        </span>
                        <div class="rf-choices">
                            @foreach($field['options'] as $index => $option)
                                <label class="rf-choice" for="{{ $id }}-{{ $index }}">
                                    <input type="radio" id="{{ $id }}-{{ $index }}"
                                           name="fields[{{ $field['name'] }}]" value="{{ $option }}"
                                           @checked($value === $option) @if($field['required']) required @endif>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        @break

                    @case('checkboxes')
                        <span class="rf-label">
{!! $ico !!}{!! $label !!}@if($field['required'])<span class="rf-req">*</span>@endif
                        </span>
                        <div class="rf-choices">
                            @foreach($field['options'] as $index => $option)
                                <label class="rf-choice" for="{{ $id }}-{{ $index }}">
                                    <input type="checkbox" id="{{ $id }}-{{ $index }}"
                                           name="fields[{{ $field['name'] }}][]" value="{{ $option }}"
                                           @checked(in_array($option, (array) $value, true))>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        @break

                    @case('checkbox')
                    @case('consent')
                        <label class="rf-choice rf-consent" for="{{ $id }}">
                            <input type="checkbox" id="{{ $id }}" name="fields[{{ $field['name'] }}]" value="1"
                                   @checked((bool) $value) @if($field['required']) required @endif>
                            {{-- Текст согласия почти всегда содержит ссылку на
                                 политику — она пишется как [текст](/privacy) и
                                 разворачивается форматтером. Сырой HTML сюда
                                 больше не попадает: он экранируется. --}}
                            <span>{!! $ico !!}{!! $label !!}@if($field['required'])<span class="rf-req">*</span>@endif</span>
                        </label>
                        @break

                    @case('file')
                        <label class="rf-label" for="{{ $id }}">
{!! $ico !!}{!! $label !!}@if($field['required'])<span class="rf-req">*</span>@endif
                        </label>
                        <input type="file" id="{{ $id }}" name="fields[{{ $field['name'] }}]" class="rf-input rf-file"
                               @if($field['required']) required @endif>
                        @break

                    @default
                        <label class="rf-label" for="{{ $id }}">
{!! $ico !!}{!! $label !!}@if($field['required'])<span class="rf-req">*</span>@endif
                        </label>
                        <input type="{{ $field['type'] }}" id="{{ $id }}" name="fields[{{ $field['name'] }}]"
                               class="rf-input" value="{{ $value }}" placeholder="{{ $field['placeholder'] }}"
                               @if($field['required']) required @endif>
                @endswitch

                @if($field['hint'] && ! in_array($field['type'], ['heading', 'paragraph', 'hidden'], true))
                    <small class="rf-hint">{!! \Modules\Forms\Services\FormService::format($field['hint']) !!}</small>
                @endif
            </div>
        @endforeach

        @if($form->setting('captcha') && function_exists('captcha_preset'))
            <div class="rf-row is-wide rf-captcha">
                {!! captcha_preset($form->setting('captcha')) !!}
            </div>
        @endif

        <div class="rf-row is-wide rf-actions">
            <button type="submit" class="rf-btn">{{ $form->setting('submit_label') ?: __('forms.submit') }}</button>
            @if($form->setting('note'))
                <small class="rf-note">{{ $form->setting('note') }}</small>
            @endif
        </div>
    </form>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/forms.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/forms.js') }}" defer></script>
    @endpush
@endonce
