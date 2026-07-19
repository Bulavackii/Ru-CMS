@extends('layouts.admin')

@section('title', 'Новое слайдшоу')
@section('header', 'Создание слайдшоу')

@section('content')
@php
    $pos = old('position', 'top');
@endphp

{{-- Заголовок + «к списку» --}}
<div class="flex items-start justify-between mb-5 gap-3">
    <div class="space-y-1">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🎞️ Новое слайдшоу</h1>
        <p class="text-sm text-gray-500">
            Дайте понятное название и укажите, где показывать блок на главной.
        </p>
    </div>

    <a href="{{ route('admin.slideshow.index') }}"
       class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100 text-sm
              dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
       title="Вернуться к списку (Esc)">
        <i class="fa-regular fa-circle-left"></i> К списку
    </a>
</div>

{{-- Ошибки --}}
@if ($errors->any())
  <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200">
      <div class="font-semibold mb-1">Проверьте форму:</div>
      <ul class="list-disc pl-5 space-y-0.5">
          @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
          @endforeach
      </ul>
  </div>
@endif

<form id="slideshow-form" method="POST" action="{{ route('admin.slideshow.store') }}" class="max-w-3xl">
    @csrf

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6">

        {{-- Название --}}
        <div>
            <label for="title" class="block font-semibold mb-1 text-gray-800 dark:text-gray-200">🏷️ Название слайдшоу</label>
            <div class="relative">
                <input type="text" name="title" id="title" required value="{{ old('title') }}"
                       placeholder="Например: «Хедер 1»"
                       class="peer w-full h-11 border border-gray-300 dark:border-gray-700 rounded-md px-3
                              bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                              focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                       autocomplete="off" />
                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-300 peer-focus:text-blue-400">
                    <i class="fa-regular fa-pen-to-square"></i>
                </div>
            </div>
            <p class="mt-1 text-xs text-gray-500">
                Это внутреннее имя в панели. На сайте пользователи его не увидят.
            </p>
        </div>

        {{-- Позиция (чипы с яркой заливкой) --}}
        <div>
            <span class="block font-semibold mb-2 text-gray-800 dark:text-gray-200">📍 Позиция на сайте</span>

            <div class="inline-flex items-center gap-2" role="radiogroup" aria-label="Позиция на сайте">
                {{-- ВВЕРХУ --}}
                <input class="peer/top sr-only" type="radio" id="pos-top" name="position" value="top"
                       {{ $pos === 'top' ? 'checked' : '' }}>
                <label for="pos-top"
                       class="select-none px-3 py-1.5 rounded-full text-sm transition-colors duration-150
                              ring-2 ring-blue-500 text-blue-700 bg-white
                              hover:bg-blue-600 hover:text-white hover:ring-blue-600
                              focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-300
                              dark:bg-gray-800 dark:text-blue-300
                              peer-checked/top:bg-blue-600 peer-checked/top:text-white peer-checked/top:ring-blue-600">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <i class="fa-solid fa-arrow-up"></i> Вверху
                    </span>
                </label>

                {{-- ВНИЗУ --}}
                <input class="peer/btm sr-only" type="radio" id="pos-bottom" name="position" value="bottom"
                       {{ $pos === 'bottom' ? 'checked' : '' }}>
                <label for="pos-bottom"
                       class="select-none px-3 py-1.5 rounded-full text-sm transition-colors duration-150
                              ring-2 ring-amber-500 text-amber-700 bg-white
                              hover:bg-amber-600 hover:text-white hover:ring-amber-600
                              focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-amber-300
                              dark:bg-gray-800 dark:text-amber-300
                              peer-checked/btm:bg-amber-600 peer-checked/btm:text-white peer-checked/btm:ring-amber-600">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <i class="fa-solid fa-arrow-down"></i> Внизу
                    </span>
                </label>
            </div>

            <p id="pos-hint" class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                Появится <span data-pos="top" class="{{ $pos==='top' ? '' : 'hidden' }}">над контентом (вверху).</span>
                <span data-pos="bottom" class="{{ $pos==='bottom' ? '' : 'hidden' }}">после блоков (внизу).</span>
            </p>
        </div>

        {{-- Публикация --}}
        <div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="published" value="1" 
                       {{ old('published', false) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800">
                <div>
                    <span class="block font-semibold text-gray-800 dark:text-gray-200">✅ Опубликовать сразу</span>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Если не отмечено, слайдшоу будет скрыто от посетителей
                    </p>
                </div>
            </label>
        </div>

        {{-- Подсказки --}}
        <aside class="bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-xs text-gray-600 dark:text-gray-300">
            <ul class="list-disc pl-5 space-y-1">
                <li><b>Ctrl + Enter</b> — создать;</li>
                <li><b>T</b> — выбрать «Вверху», <b>B</b> — «Внизу»;</li>
                <li><b>Esc</b> — вернуться к списку.</li>
            </ul>
            <div class="mt-2 text-[11px] text-gray-500">
                После сохранения на странице списка появится шорткод для вставки в шаблон.
            </div>
        </aside>

        {{-- Кнопка --}}
        <div class="pt-2 flex items-center justify-end">
            <button id="submit-btn" type="submit"
                    class="inline-flex items-center gap-2 px-4 h-10 rounded-md bg-black text-white hover:bg-gray-800 shadow
                           disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Создать (Ctrl + Enter)">
                <i class="fa-solid fa-floppy-disk"></i> Создать
            </button>
        </div>
    </div>
</form>

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
