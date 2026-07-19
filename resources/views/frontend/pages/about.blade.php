@extends('layouts.frontend')

@section('title', 'О компании — Монтаж металлоконструкций, эвакуация авто, спил деревьев, подъём грузов')

@section('content')
<article class="max-w-4xl mx-auto px-4">
  {{-- Хиро-блок --}}
  <header class="rounded-lg md:rounded-xl p-6 md:p-8 border shadow-sm mb-8"
          style="border-color: color-mix(in oklab, var(--color-text,#111827) 10%, #e5e7eb); background:#fff">
    <div class="flex items-start md:items-center gap-4 md:gap-6 flex-col md:flex-row">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg"
           style="background: color-mix(in oklab, var(--color-primary,#2563eb) 12%, #fff);">
        @themeIcon('shield','w-5 h-5 opacity-80')
      </div>
      <div class="flex-1">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight"
            style="color: var(--color-text,#111827)">О компании</h1>
        <p class="mt-2 text-sm md:text-base opacity-80" style="color: var(--color-text,#111827)">
          Мы — бригада, которая закрывает «силовые» задачи на объектах:
          <span class="font-semibold">монтаж металлоконструкций, эвакуация авто, спиливание деревьев и подъём грузов на высоту</span>.
          Работаем аккуратно и по технологии: расчёт узлов строповки, безопасные зоны, согласованная работа машиниста и стропальщика.
          Выезжаем оперативно, режим <b>24/7</b> для срочных заявок.
        </p>
      </div>
    </div>
  </header>

  {{-- Основной блок содержимого --}}
  <section class="rounded-lg md:rounded-xl p-6 md:p-8 border shadow-sm space-y-8"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 10%, #e5e7eb); background:#fff">

    {{-- Почему мы (мини-сетка) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
      <div class="p-4 rounded-lg border"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 10%, #e5e7eb)">
        <div class="flex items-start gap-3">
          <span class="inline-flex w-8 h-8 items-center justify-center rounded-md"
                style="background: color-mix(in oklab, var(--color-primary,#2563eb) 10%, #fff);">
            @themeIcon('shield','w-4 h-4 opacity-80')
          </span>
          <div>
            <h3 class="font-semibold" style="color: var(--color-text,#111827)">Техника + регламент</h3>
            <p class="text-sm opacity-80 mt-1">Работаем по ППР: план подъёма, точки анкеровки, схемы строповки, ограждение периметра. Инструмент и спецсредства под задачу.</p>
          </div>
        </div>
      </div>

      <div class="p-4 rounded-lg border"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 10%, #e5e7eb)">
        <div class="flex items-start gap-3">
          <span class="inline-flex w-8 h-8 items-center justify-center rounded-md"
                style="background: color-mix(in oklab, var(--color-primary,#2563eb) 10%, #fff);">
            @themeIcon('scissors','w-4 h-4 opacity-80')
          </span>
          <div>
            <h3 class="font-semibold" style="color: var(--color-text,#111827)">Точно и чисто</h3>
            <p class="text-sm opacity-80 mt-1">Точные распилы и сборка узлов; бережём кровлю, фасады, коммуникации. После работ — уборка и вывоз.</p>
          </div>
        </div>
      </div>

      <div class="p-4 rounded-lg border"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 10%, #e5e7eb)">
        <div class="flex items-start gap-3">
          <span class="inline-flex w-8 h-8 items-center justify-center rounded-md"
                style="background: color-mix(in oklab, var(--color-primary,#2563eb) 10%, #fff);">
            @themeIcon('truck','w-4 h-4 opacity-80')
          </span>
          <div>
            <h3 class="font-semibold" style="color: var(--color-text,#111827)">Своя спецтехника</h3>
            <p class="text-sm opacity-80 mt-1">Манипулятор, эвакуатор, высотные решения, такелаж. Меньше подрядчиков — быстрее запуск.</p>
          </div>
        </div>
      </div>

      <div class="p-4 rounded-lg border"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 10%, #e5e7eb)">
        <div class="flex items-start gap-3">
          <span class="inline-flex w-8 h-8 items-center justify-center rounded-md"
                style="background: color-mix(in oklab, var(--color-primary,#2563eb) 10%, #fff);">
            @themeIcon('lock','w-4 h-4 opacity-80')
          </span>
          <div>
            <h3 class="font-semibold" style="color: var(--color-text,#111827)">Полный цикл</h3>
            <p class="text-sm opacity-80 mt-1">От осмотра и сметы до монтажа/демонтажа и вывоза. Один подрядчик — весь результат.</p>
          </div>
        </div>
      </div>

      <div class="p-4 rounded-lg border sm:col-span-2"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 10%, #e5e7eb)">
        <div class="flex items-start gap-3">
          <span class="inline-flex w-8 h-8 items-center justify-center rounded-md"
                style="background: color-mix(in oklab, var(--color-primary,#2563eb) 10%, #fff);">
            @themeIcon('clock','w-4 h-4 opacity-80')
          </span>
          <div class="flex-1">
            <h3 class="font-semibold" style="color: var(--color-text,#111827)">Режим 24/7</h3>
            <p class="text-sm opacity-80 mt-1">Аварийные выезды ночью и в выходные. Оперативная связь и быстрый запуск работ.</p>
          </div>
        </div>
      </div>
    </div>

    {{-- Чем мы занимаемся (список услуг) --}}
    <div class="space-y-3">
      <h2 class="text-lg font-semibold" style="color: var(--color-text,#111827)">Направления работ</h2>
      <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        @foreach ([
          'Монтаж металлоконструкций: сборка ферм, колонн, балок; выверка, болтовые и сварные соединения, контроль геометрии.',
          'Эвакуация авто: легковые, кроссоверы, спецтехника; бережная погрузка, сложные случаи (блокировка колёс/АКП).',
          'Спиливание деревьев: аварийные и труднодоступные зоны, распил по секциям, страховка, вывоз порубочных остатков.',
          'Подъём грузов на высоту: такелаж, строповка по схеме, безопасная траектория, работа в стеснённых дворах.'
        ] as $item)
          <li class="flex items-start gap-2">
            <span class="inline-flex w-5 h-5 items-center justify-center mt-0.5">
              @themeIcon('check-circle-2','w-4 h-4 opacity-80')
            </span>
            <span class="text-sm opacity-90" style="color: var(--color-text,#111827)">{{ $item }}</span>
          </li>
        @endforeach
      </ul>
    </div>

    {{-- Как мы работаем (процесс) --}}
    <div class="space-y-3">
      <h2 class="text-lg font-semibold" style="color: var(--color-text,#111827)">Как мы работаем</h2>
      <ol class="space-y-2 text-sm opacity-90" style="color: var(--color-text,#111827)">
        <li><span class="font-medium">Осмотр и бриф.</span> Фото/видео или выезд. Уточняем веса, габариты, препятствия, подъезд.</li>
        <li><span class="font-medium">Технология и ППР.</span> Схема строповки, точки крепления, порядок работ, ограждение зоны.</li>
        <li><span class="font-medium">Смета.</span> Прозрачная стоимость фиксируется до начала — без скрытых доплат.</li>
        <li><span class="font-medium">Безопасность.</span> Связь машинист–стропальщик, сигналы, каски/привязи, контроль ветра/климата.</li>
        <li><span class="font-medium">Выполнение.</span> Точная подача, монтаж/распил по секциям, аккуратный спуск и установка.</li>
        <li><span class="font-medium">Завершение.</span> Уборка, вывоз, приёмка с заказчиком.</li>
      </ol>
    </div>

    {{-- Небольшой блок фактов/статистики --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
      <div class="rounded-md border p-4 text-center"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 10%, #e5e7eb)">
        <div class="text-2xl font-extrabold" style="color: var(--color-text,#111827)">⚙️</div>
        <div class="mt-1 text-sm opacity-80">Собственная спецтехника</div>
      </div>
      <div class="rounded-md border p-4 text-center"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 10%, #e5e7eb)">
        <div class="text-2xl font-extrabold" style="color: var(--color-text,#111827)">🕘</div>
        <div class="mt-1 text-sm opacity-80">Оперативный выезд</div>
      </div>
      <div class="rounded-md border p-4 text-center"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 10%, #e5e7eb)">
        <div class="text-2xl font-extrabold" style="color: var(--color-text,#111827)">🛡️</div>
        <div class="mt-1 text-sm opacity-80">Технологично и безопасно</div>
      </div>
    </div>

    {{-- CTA --}}
    <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between pt-2">
      <a href="{{ url('/contacts') }}"
         class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-md text-white"
         style="background: var(--color-primary,#2563eb)">
        @themeIcon('phone','w-4 h-4')
        <span class="font-semibold">Оставить заявку</span>
      </a>

      <div class="flex gap-3">
        <a href="https://vk.com/club228649931" target="_blank"
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-md border"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 12%, #e5e7eb)">
          @themeIcon('send','w-4 h-4')
          <span>Мы во ВКонтакте</span>
        </a>

        <a href="tel:+79300373536"
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-md border"
           style="border-color: color-mix(in oklab, var(--color-text,#111827) 12%, #e5e7eb)">
          @themeIcon('phone','w-4 h-4')
          <span>Позвонить 24/7</span>
        </a>
      </div>
    </div>

  </section>
</article>
@endsection
