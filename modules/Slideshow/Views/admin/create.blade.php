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

{{-- Ошибки --}}
@if ($errors->any())
  <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200 px-4 py-3 mb-6 text-sm">
      <div class="flex items-start gap-2">
          <i class="fas fa-triangle-exclamation mt-0.5"></i>
          <div>
              <div class="font-semibold mb-1">{{ __('admin.slideshow.check_form') }}</div>
              <ul class="list-disc pl-5 space-y-0.5">
                  @foreach ($errors->all() as $e)
                      <li>{{ $e }}</li>
                  @endforeach
              </ul>
          </div>
      </div>
  </div>
@endif

<form id="slideshow-form" method="POST" action="{{ route('admin.slideshow.store') }}" class="w-full">
    @csrf

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6">

        {{-- Название --}}
        <div>
            <label for="title" class="block font-semibold mb-1 text-gray-800 dark:text-gray-200">🏷️ {{ __('admin.slideshow.name') }}</label>
            <div class="relative">
                <input type="text" name="title" id="title" required value="{{ old('title') }}"
                       placeholder="{{ __('admin.slideshow.name_ph') }}"
                       class="peer w-full h-11 border border-gray-300 dark:border-gray-700 rounded-md px-3
                              bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                              focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                       autocomplete="off" />
                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-300 peer-focus:text-blue-400">
                    <i class="fa-regular fa-pen-to-square"></i>
                </div>
            </div>
            <p class="mt-1 text-xs text-gray-500">
                {{ __('admin.slideshow.name_hint') }}
            </p>
        </div>

        {{-- Позиция.
             ⚠️ Раньше подсветка выбранного варианта делалась через peer-checked/top:
             и peer-checked/btm: — этих вариантов в собранном tailwind.min.css НЕТ
             (как и палитры amber), поэтому выбор визуально не отображался вовсе.
             Теперь — настоящий CSS-селектор input:checked + label (см. <style> ниже). --}}
        <div>
            <span class="block font-semibold mb-2 text-gray-800 dark:text-gray-200">{{ __('admin.slideshow.position') }}</span>

            <div class="pos-switch inline-flex items-center gap-2" role="radiogroup" aria-label="{{ __('admin.slideshow.position') }}">
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

            <p id="pos-hint" class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                {{ __('admin.slideshow.will_appear') }} <span data-pos="top" class="{{ $pos==='top' ? '' : 'hidden' }}">{{ __('admin.slideshow.above_content') }}</span>
                <span data-pos="bottom" class="{{ $pos==='bottom' ? '' : 'hidden' }}">{{ __('admin.slideshow.below_blocks') }}</span>
            </p>
        </div>

        {{-- Публикация --}}
        <div>
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="published" value="1"
                       {{ old('published', true) ? 'checked' : '' }}
                       class="w-4 h-4 mt-0.5">
                <div>
                    <span class="block font-semibold text-gray-800 dark:text-gray-200">{{ __('admin.slideshow.publish_now') }}</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('admin.slideshow.publish_hint') }}
                    </p>
                </div>
            </label>
        </div>

        {{-- Подсказки --}}
        <aside class="bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-xs text-gray-600 dark:text-gray-300">
            <ul class="list-disc pl-5 space-y-1">
                <li><b>Ctrl + Enter</b> {{ __('admin.slideshow.hk_create') }}</li>
                <li><b>T</b> {{ __('admin.slideshow.hk_top') }} <b>B</b> {{ __('admin.slideshow.hk_bottom') }}</li>
                <li><b>Esc</b> {{ __('admin.slideshow.hk_back') }}</li>
            </ul>
            <div class="mt-2 text-[11px] text-gray-500">
                {{ __('admin.slideshow.shortcode_note') }}
            </div>
        </aside>

        {{-- Кнопка --}}
        <div class="pt-2 flex items-center justify-end">
            <button id="submit-btn" type="submit"
                    class="inline-flex items-center gap-2 px-5 h-10 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition
                           disabled:opacity-50 disabled:cursor-not-allowed"
                    title="{{ __('admin.slideshow.create_hk') }}">
                <i class="fa-solid fa-floppy-disk"></i> {{ __('admin.slideshow.create') }}
            </button>
        </div>
    </div>
</form>

{{-- Чипы выбора позиции: литеральный CSS вместо отсутствующих в сборке
     peer-checked/*-вариантов (см. CLAUDE.md про неполную Tailwind-сборку). --}}
<style>
    .pos-switch .pos-chip{
        display:inline-flex; align-items:center; gap:.4rem; cursor:pointer; user-select:none;
        padding:.45rem .85rem; font-size:.875rem; font-weight:500;
        border:1px solid #d1d5db; background:#fff; color:#374151;
        transition:background .15s ease, color .15s ease, border-color .15s ease;
    }
    .dark .pos-switch .pos-chip{ background:#111827; border-color:#374151; color:#d1d5db; }
    .pos-switch .pos-chip:hover{ border-color:#818cf8; color:#4f46e5; }
    .pos-switch input:checked + .pos-chip{
        background:#4f46e5; border-color:#4f46e5; color:#fff;
        box-shadow:0 8px 18px -10px rgba(99,102,241,.7);
    }
    .pos-switch input:focus-visible + .pos-chip{ outline:2px solid #818cf8; outline-offset:2px; }
</style>

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
