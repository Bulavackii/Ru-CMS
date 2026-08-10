@extends('layouts.admin')

@section('title', __('admin.slideshow.new'))
@section('header', __('admin.slideshow.creating'))

@section('content')
@php
    $pos = old('position', 'top');
@endphp

{{-- ── Шапка страницы ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-images"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.slideshow.new') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.slideshow.new_hint') }}
            </p>
        </div>
    </div>

    <a href="{{ route('admin.slideshow.index') }}"
       class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition shrink-0"
       title="{{ __('admin.slideshow.back_esc') }}">
        <i class="fa-solid fa-arrow-left"></i> {{ __('admin.slideshow.back') }}
    </a>
</div>

{{-- Ошибки.
     `dark:bg-red-900/30` в этой сборке не рендерится (прозрачности через
     дробь в ней нет вовсе) — подложка была прозрачной. --}}
@if ($errors->any())
  <div class="sl-errors">
      <i class="fas fa-triangle-exclamation"></i>
      <div>
          <div class="sl-errors__title">{{ __('admin.slideshow.check_form') }}</div>
          <ul>
              @foreach ($errors->all() as $e)
                  <li>{{ $e }}</li>
              @endforeach
          </ul>
      </div>
  </div>
@endif

{{-- Две колонки: форма и что будет дальше. Раньше поле названия тянулось
     на всю ширину экрана — полторы тысячи пикселей под короткую строку, —
     а справа от него была пустота. --}}
<div class="sl-create">

    <form id="slideshow-form" method="POST" action="{{ route('admin.slideshow.store') }}" class="admin-card">
        @csrf

        <div class="sl-cardhead">
            <i class="fas fa-sliders"></i> {{ __('admin.slideshow.settings') }}
        </div>

        <div class="sl-create__body">
            <div class="sl-field">
                <label class="sl-label" for="title"><i class="fas fa-tag"></i> {{ __('admin.slideshow.name') }}</label>
                <input type="text" name="title" id="title" required value="{{ old('title') }}"
                       placeholder="{{ __('admin.slideshow.name_ph') }}"
                       class="sl-input" autocomplete="off">
                <span class="sl-hint">{{ __('admin.slideshow.name_hint') }}</span>
            </div>

            {{-- Позиция.
                 ⚠️ Раньше подсветка выбранного варианта делалась через
                 peer-checked/top: и peer-checked/btm: — этих вариантов в
                 собранном tailwind.min.css НЕТ, поэтому выбор визуально не
                 отображался вовсе. Теперь настоящий селектор
                 input:checked + label. --}}
            <div class="sl-field">
                <span class="sl-label"><i class="fas fa-location-dot"></i> {{ __('admin.slideshow.position') }}</span>

                <div class="pos-switch" role="radiogroup" aria-label="{{ __('admin.slideshow.position') }}">
                    <input class="sr-only" type="radio" id="pos-top" name="position" value="top"
                           {{ $pos === 'top' ? 'checked' : '' }}>
                    <label for="pos-top" class="pos-chip">
                        <i class="fa-solid fa-arrow-up"></i> {{ __('admin.slideshow.top') }}
                    </label>

                    <input class="sr-only" type="radio" id="pos-bottom" name="position" value="bottom"
                           {{ $pos === 'bottom' ? 'checked' : '' }}>
                    <label for="pos-bottom" class="pos-chip">
                        <i class="fa-solid fa-arrow-down"></i> {{ __('admin.slideshow.bottom') }}
                    </label>
                </div>

                <span id="pos-hint" class="sl-hint">
                    {{ __('admin.slideshow.will_appear') }}
                    <span data-pos="top" class="{{ $pos==='top' ? '' : 'hidden' }}">{{ __('admin.slideshow.above_content') }}</span>
                    <span data-pos="bottom" class="{{ $pos==='bottom' ? '' : 'hidden' }}">{{ __('admin.slideshow.below_blocks') }}</span>
                </span>
            </div>

            {{-- Тот же тумблер, что в настройках готового слайдшоу: голая
                 галочка рядом с ним выглядела другим элементом. --}}
            <div class="sl-field">
                <label class="sl-switch">
                    <span class="admin-toggle">
                        <input type="checkbox" name="published" value="1" {{ old('published', true) ? 'checked' : '' }}>
                        <span class="track"></span><span class="knob"></span>
                    </span>
                    <span>{{ __('admin.slideshow.publish_now') }}</span>
                </label>
                <span class="sl-hint">{{ __('admin.slideshow.publish_hint') }}</span>
            </div>
        </div>

        <div class="sl-create__foot">
            <a href="{{ route('admin.slideshow.index') }}" class="sl-btn">{{ __('admin.slideshow.back_short') }}</a>
            <button id="submit-btn" type="submit" class="sl-btn sl-btn--primary"
                    title="{{ __('admin.slideshow.create_hk') }}">
                <i class="fa-solid fa-floppy-disk"></i> {{ __('admin.slideshow.create') }}
            </button>
        </div>
    </form>

    {{-- Что дальше. Раньше об этом сообщала одна строка мелким шрифтом под
         списком горячих клавиш, и порядок действий приходилось угадывать. --}}
    <aside class="admin-card sl-next">
        <div class="sl-cardhead">
            <i class="fas fa-list-check"></i> {{ __('admin.slideshow.next_title') }}
        </div>

        <ol class="sl-steps">
            <li><span class="sl-steps__n">1</span><span>{{ __('admin.slideshow.next_1') }}</span></li>
            <li><span class="sl-steps__n">2</span><span>{{ __('admin.slideshow.next_2') }}</span></li>
            <li><span class="sl-steps__n">3</span><span>{{ __('admin.slideshow.next_3') }}</span></li>
        </ol>

        <div class="sl-keys">
            <span class="sl-keys__title">{{ __('admin.slideshow.hotkeys_title') }}</span>
            <ul>
                <li><kbd>Ctrl</kbd><kbd>Enter</kbd> {{ __('admin.slideshow.hk_create') }}</li>
                <li><kbd>T</kbd> {{ __('admin.slideshow.hk_top') }} <kbd>B</kbd> {{ __('admin.slideshow.hk_bottom') }}</li>
                <li><kbd>Esc</kbd> {{ __('admin.slideshow.hk_back') }}</li>
            </ul>
        </div>
    </aside>
</div>

@include('Slideshow::admin.partials.styles')



{{-- Мини-скрипт UX: блокируем кнопку без названия, горячие клавиши, живой хинт позиции --}}
<script>
(function(){
  const title   = document.getElementById('title');
  const form    = document.getElementById('slideshow-form');
  const submit  = document.getElementById('submit-btn');
  const posTop  = document.getElementById('pos-top');
  const posBtm  = document.getElementById('pos-bottom');
  const topHint = document.querySelector('[data-pos="top"]');
  const btmHint = document.querySelector('[data-pos="bottom"]');

  function syncSubmitState(){
    submit.disabled = !title.value.trim();
  }
  function syncHint(){
    if (posTop.checked){ topHint.classList.remove('hidden'); btmHint.classList.add('hidden'); }
    else { btmHint.classList.remove('hidden'); topHint.classList.add('hidden'); }
  }

  // init
  syncSubmitState();
  syncHint();

  title.addEventListener('input', syncSubmitState);
  posTop.addEventListener('change', syncHint);
  posBtm.addEventListener('change', syncHint);

  // hotkeys
  document.addEventListener('keydown', (e) => {
    const meta = e.ctrlKey || e.metaKey;

    // Ctrl+Enter -> submit
    if (meta && e.key === 'Enter'){
      if (!submit.disabled) form.requestSubmit();
    }

    // T/B -> position
    if (!e.target.matches('input, textarea')){
      if (e.key.toLowerCase() === 't'){ posTop.checked = true; syncHint(); }
      if (e.key.toLowerCase() === 'b'){ posBtm.checked = true; syncHint(); }
      if (e.key === 'Escape'){ window.location.href = @json(route('admin.slideshow.index')); }
    }
  });
})();
</script>
@endsection
