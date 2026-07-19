@extends('layouts.frontend')

@section('title', 'Разработчикам — RU CMS')

@push('head')
  <meta name="description" content="Разработчикам RU CMS: стек и архитектура, принципы модульности, как подключиться к разработке. Контакты команды разработчиков: VK @ru_cms, Telegram @ru_cms, e-mail visitorsec@internet.ru." />
@endpush

@section('content')
<section class="max-w-4xl mx-auto bg-white border border-gray-300 rounded-2xl shadow-xl p-8 md:p-12 text-[15px] leading-relaxed text-gray-800 space-y-8">

  <h1 class="text-3xl font-extrabold text-blue-800 text-center">💻 Разработчикам RU CMS</h1>

  {{-- Лид --}}
  <p class="text-gray-700 text-center max-w-3xl mx-auto">
    <strong>RU CMS</strong> — модульная CMS на Laravel с чистой архитектурой и удобной кастомизацией. Ниже — краткий гайд по стеку, модулям и форматам участия. Для вопросов по разработке используйте контакты команды ниже.
  </p>

  {{-- 🧠 Стек и требования --}}
  <div>
    <h2 class="text-xl font-semibold text-blue-700 mb-2">🧠 Стек и требования</h2>
    <ul class="list-disc pl-6 space-y-1">
      <li><strong>PHP</strong> 8.5, <strong>Laravel</strong> 12.x</li>
      <li><strong>Blade</strong> шаблоны, <strong>Tailwind CSS</strong> для UI</li>
      <li><strong>HMVC-модули</strong>: ядро + независимые модули (как плагины)</li>
      <li>Встроенная поддержка: маршруты, миграции, категории, меню, слайдшоу, новости</li>
    </ul>
  </div>

  {{-- 🧩 Архитектура модулей (кратко) --}}
  <div>
    <h2 class="text-xl font-semibold text-blue-700 mb-2">🧩 Архитектура модулей</h2>
    <ul class="list-disc pl-6 space-y-1">
      <li>Каждый модуль живёт в <code>/modules/&lt;ModuleName&gt;</code> и автономен</li>
      <li>Регистрация через <code>ModuleServiceProvider</code> (routes, views, миграции)</li>
      <li>Маршруты модуля — <code>Routes/web.php</code>, представления — <code>Resources/views</code></li>
      <li>Манифест (метаданные) — <code>module.json</code></li>
    </ul>
  </div>

  {{-- ⚙️ Быстрый старт для разработки --}}
  <div>
    <h2 class="text-xl font-semibold text-blue-700 mb-2">⚙️ Быстрый старт</h2>
    <ol class="list-decimal pl-6 space-y-1">
      <li>Клонируйте проект и установите зависимости (<code>composer install</code>)</li>
      <li>Создайте <code>.env</code>, сгенерируйте ключ (<code>php artisan key:generate</code>)</li>
      <li>Запустите миграции/сидеры по необходимости (<code>php artisan migrate --seed</code>)</li>
      <li>Включайте нужные модули в админке или через сиды</li>
    </ol>
    <p class="text-gray-600 mt-2">Подробные примеры кода будут в документации проекта.</p>
  </div>

  {{-- 📚 Ресурсы (не контакты) --}}
  <div>
    <h2 class="text-xl font-semibold text-blue-700 mb-2">📚 Ресурсы</h2>
    <ul class="list-disc pl-6 space-y-1">
      <li>Репозиторий GitHub: Проект в закрытом доступе</li>
      <li>Демо-шаблоны и примеры: в составе дистрибутива</li>
      <li>Документация: раздел в админ-панели (в работе)</li>
    </ul>
  </div>

  {{-- ✉️ Контакты разработчиков (только dev-связь) --}}
  <div class="bg-blue-50 border border-blue-100 rounded-xl p-5">
    <h2 class="text-lg font-semibold text-blue-800 mb-3">✉️ Связь с разработчиками RU CMS</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <a href="https://vk.com/ru_cms" target="_blank" rel="noopener"
         class="flex items-center justify-between px-3 py-2 rounded-lg border hover:shadow-sm transition"
         style="border-color:#e5e7eb;">
        <span class="inline-flex items-center gap-2" style="color:var(--color-primary,#2563eb)">
          @themeIcon('vk') VK
        </span>
        @themeIcon('arrow-up-right-from-square')
      </a>

      <a href="https://t.me/ru_cms" target="_blank" rel="noopener"
         class="flex items-center justify-between px-3 py-2 rounded-lg border hover:shadow-sm transition"
         style="border-color:#e5e7eb;">
        <span class="inline-flex items-center gap-2" style="color:var(--color-primary,#2563eb)">
          @themeIcon('telegram-plane') Telegram
        </span>
        @themeIcon('arrow-up-right-from-square')
      </a>

      <a href="mailto:visitorsec@internet.ru?subject=RU%20CMS%3A%20вопрос%20по%20разработке&body=%D0%9A%D1%80%D0%B0%D1%82%D0%BA%D0%BE%20%D0%BE%EF%BF%BD%EF%BF%BD%D0%BE%D0%BF%D0%B8%D1%88%D0%B8%D1%82%D0%B5%20%D0%B7%D0%B0%D0%B4%D0%B0%D1%87%D1%83%2C%20%D0%B4%D0%B5%D0%BF%D0%BB%D0%BE%D0%B9%2F%D1%81%D1%80%D0%B5%D0%B4%D1%83%20%D0%B8%20%D1%81%D1%80%D0%BE%D0%BA%D0%B8."
         class="flex items-center justify-between px-3 py-2 rounded-lg border hover:shadow-sm transition"
         style="border-color:#e5e7eb;">
        <span class="inline-flex items-center gap-2" style="color:var(--color-primary,#2563eb)">
          @themeIcon('mail') E-mail
        </span>
        @themeIcon('arrow-up-right-from-square')
      </a>
    </div>
    <p class="text-xs text-gray-600 mt-3">Контакты выше предназначены только для технических вопросов и предложений по разработке RU CMS.</p>
  </div>

  <p class="text-center text-sm text-gray-500">Последнее обновление: {{ date('d.m.Y') }}</p>

  <div class="text-center mt-8">
    <a href="{{ url('/') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg shadow hover:bg-blue-700 transition">
      ← На главную
    </a>
  </div>
</section>
@endsection

@push('head')
{{-- Structured Data: ContactPage (dev contacts only) --}}
<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'ContactPage',
  'name' => 'Связь с разработчиками RU CMS',
  'mainEntityOfPage' => url()->current(),
  'url' => url()->current(),
  'inLanguage' => 'ru-RU',
  'about' => [
    '@type' => 'SoftwareApplication',
    'name' => 'RU CMS',
    'applicationCategory' => 'WebApplication',
    'operatingSystem' => 'Linux/Unix'
  ],
  'contactPoint' => [[
    '@type' => 'ContactPoint',
    'contactType' => 'technical support',
    'email' => 'visitorsec@internet.ru',
    'areaServed' => 'RU',
    'availableLanguage' => ['ru']
  ]],
  'sameAs' => [
    'https://vk.com/ru_cms',
    'https://t.me/ru_cms'
  ]
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
