<section
  class="relative overflow-hidden rounded-2xl mb-12"
  style="
    --about-bg: linear-gradient(135deg, color-mix(in oklab, var(--color-primary,#2563eb) 12%, white), white 40%, color-mix(in oklab, var(--color-accent,#10b981) 10%, white));
    background: var(--about-bg);
  "
>
  {{-- декоративные круги --}}
  <div class="pointer-events-none absolute -top-20 -left-24 w-72 h-72 rounded-full opacity-10"
       style="background: var(--color-primary,#2563eb)"></div>
  <div class="pointer-events-none absolute -bottom-20 -right-24 w-72 h-72 rounded-full opacity-10"
       style="background: var(--color-accent,#10b981)"></div>

  <div class="relative z-10 p-6 md:p-10 lg:p-12">
    {{-- Мета-заголовок --}}
    <div class="flex items-center justify-center gap-2 mb-3 select-none">
      <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold tracking-wide"
            style="background: color-mix(in oklab, var(--color-primary,#2563eb) 12%, white); color: var(--color-text,#111827);">
        О продукте
      </span>
    </div>

    {{-- Заголовок --}}
    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-center tracking-tight mb-4"
        style="color: var(--color-text,#111827)">
      RU&nbsp;CMS —&nbsp;быстрая, безопасная и гибкая
    </h2>

    {{-- Подзаголовок --}}
    <p class="text-center max-w-3xl mx-auto text-sm sm:text-base opacity-80 mb-8"
       style="color: var(--color-text,#111827)">
      Современная CMS с модульной архитектурой: мощные инструменты контента, редакторы Тем и Фрагментов,
      а также массовый импорт/экспорт новостей — всё из коробки.
    </p>

    {{-- Фичи --}}
    @php
      $cardBase = 'rounded-xl border shadow-sm p-4 sm:p-5 bg-white/80 backdrop-blur transition hover:shadow-md';
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      {{-- Безопасность --}}
      <article class="{{ $cardBase }}" style="border-color: color-mix(in oklab, var(--color-primary,#2563eb) 12%, white)">
        <div class="flex items-start gap-3">
          <div class="text-2xl select-none">🔐</div>
          <div>
            <h3 class="font-semibold mb-1" style="color: var(--color-text,#111827)">Современная защита</h3>
            <p class="text-sm opacity-75">Хеш паролей, защита сессий, строгие политики доступа.</p>
          </div>
        </div>
      </article>

      {{-- SEO --}}
      <article class="{{ $cardBase }}" style="border-color: color-mix(in oklab, var(--color-accent,#10b981) 18%, white)">
        <div class="flex items-start gap-3">
          <div class="text-2xl select-none">📈</div>
          <div>
            <h3 class="font-semibold mb-1" style="color: var(--color-text,#111827)">SEO-готовность</h3>
            <p class="text-sm opacity-75">Корректные мета-теги, карты сайта и чистые URL.</p>
          </div>
        </div>
      </article>

      {{-- Кастомизация --}}
      <article class="{{ $cardBase }}" style="border-color: color-mix(in oklab, var(--color-primary,#2563eb) 12%, white)">
        <div class="flex items-start gap-3">
          <div class="text-2xl select-none">🎨</div>
          <div>
            <h3 class="font-semibold mb-1" style="color: var(--color-text,#111827)">Гибкое оформление</h3>
            <p class="text-sm opacity-75">Шаблоны, обложки, собственные блоки и макеты.</p>
          </div>
        </div>
      </article>

      {{-- Модули --}}
      <article class="{{ $cardBase }}" style="border-color: color-mix(in oklab, var(--color-accent,#10b981) 18%, white)">
        <div class="flex items-start gap-3">
          <div class="text-2xl select-none">🧩</div>
          <div>
            <h3 class="font-semibold mb-1" style="color: var(--color-text,#111827)">Модульная архитектура</h3>
            <p class="text-sm opacity-75">Подключайте только нужные возможности. Масштабируйтесь легко.</p>
          </div>
        </div>
      </article>

      {{-- Производительность --}}
      <article class="{{ $cardBase }}" style="border-color: color-mix(in oklab, var(--color-primary,#2563eb) 12%, white)">
        <div class="flex items-start gap-3">
          <div class="text-2xl select-none">⚡</div>
          <div>
            <h3 class="font-semibold mb-1" style="color: var(--color-text,#111827)">Высокая скорость</h3>
            <p class="text-sm opacity-75">Оптимизированные запросы и кэширование для мгновенной отдачи.</p>
          </div>
        </div>
      </article>

      {{-- Поддержка --}}
      <article class="{{ $cardBase }}" style="border-color: color-mix(in oklab, var(--color-accent,#10b981) 18%, white)">
        <div class="flex items-start gap-3">
          <div class="text-2xl select-none">🤝</div>
          <div>
            <h3 class="font-semibold mb-1" style="color: var(--color-text,#111827)">Поддержка и обновления</h3>
            <p class="text-sm opacity-75">Регулярные апдейты, документация и сообщество.</p>
          </div>
        </div>
      </article>

      {{-- 🔥 Массовый импорт/экспорт новостей (NewsIO) --}}
      <article class="{{ $cardBase }} lg:col-span-2"
               style="border-color: color-mix(in oklab, var(--color-primary,#2563eb) 16%, white)">
        <div class="flex items-start gap-3">
          <div class="text-2xl select-none">📦⇄</div>
          <div class="flex-1">
            <h3 class="font-semibold mb-1" style="color: var(--color-text,#111827)">
              Массовый импорт/экспорт новостей
            </h3>
            <p class="text-sm opacity-75">
              Импортируйте ленты и выгружайте контент пакетно (CSV/JSON/фиды). Идеально для миграций и интеграций.
            </p>
            @if(Route::has('admin.newsio.index'))
              <a href="{{ route('admin.newsio.index') }}"
                 class="inline-block mt-3 px-3 py-1.5 rounded text-white text-sm"
                 style="background: var(--color-primary,#2563eb)">
                Открыть модуль NewsIO
              </a>
            @endif
          </div>
        </div>
      </article>

      {{-- 🎛️ Редакторы Тем и Фрагментов --}}
      <article class="{{ $cardBase }}"
               style="border-color: color-mix(in oklab, var(--color-accent,#10b981) 20%, white)">
        <div class="flex items-start gap-3">
          <div class="text-2xl select-none">🎛️</div>
          <div class="flex-1">
            <h3 class="font-semibold mb-1" style="color: var(--color-text,#111827)">
              Редакторы Тем и Фрагментов
            </h3>
            <p class="text-sm opacity-75">
              Визуально меняйте цвета, шрифты, иконки и скругления; конструктор фрагментов на TinyMCE с предпросмотром.
            </p>
            <div class="flex flex-wrap gap-2 mt-3">
              @if(Route::has('admin.visual.themes.index'))
                <a href="{{ route('admin.visual.themes.index') }}"
                   class="inline-block px-3 py-1.5 rounded text-white text-sm"
                   style="background: var(--color-primary,#2563eb)">Темы</a>
              @endif
              @if(Route::has('admin.visual.fragments.index'))
                <a href="{{ route('admin.visual.fragments.index') }}"
                   class="inline-block px-3 py-1.5 rounded text-white text-sm"
                   style="background: var(--color-accent,#10b981)">Фрагменты</a>
              @endif
            </div>
          </div>
        </div>
      </article>
    </div>

    {{-- CTA --}}
    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-3">
      <a href="#"
         class="inline-flex items-center justify-center px-5 py-3 rounded-md text-white font-semibold"
         style="background: var(--color-primary,#2563eb)">
        🚀 Начать работу
      </a>
      <a href="#"
         class="inline-flex items-center justify-center px-5 py-3 rounded-md font-semibold"
         style="color: var(--color-primary,#2563eb); background: white">
        📘 Документация
      </a>
    </div>
  </div>
</section>
