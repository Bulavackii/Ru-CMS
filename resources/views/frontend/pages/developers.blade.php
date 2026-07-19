{{-- resources/views/frontend/pages/developers.blade.php --}}
@extends('layouts.frontend')

@section('title', 'Разработчикам — Ru-CMS')

@push('styles')
<style>
  .doc h2 { scroll-margin-top: 6rem; }
  .doc .section li + li { margin-top:.25rem }
  .doc .lead { text-wrap: balance; }
  /* вместо @apply — обычный CSS */
  .kbd{
    display:inline-flex;align-items:center;
    padding:.125rem .375rem;border:1px solid #e5e7eb;border-radius:.25rem;
    font-size:.75rem;line-height:1rem;background:#fff;color:#111827
  }
  .card{
    background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;padding:1rem
  }
  @media print{
    .no-print{display:none!important}
    .card{box-shadow:none!important;border-color:#ddd!important}
  }
</style>
@endpush

@section('content')
<section class="doc max-w-5xl mx-auto bg-white border border-gray-200 rounded-2xl shadow-lg p-6 md:p-10 text-[15px] leading-relaxed text-gray-800 space-y-8">

  {{-- HERO --}}
  <header class="text-center space-y-3">
    <h1 class="text-3xl md:text-4xl font-extrabold text-blue-800">💻 Разработчикам</h1>
    <p class="lead text-gray-600">
      <strong>Ru-CMS</strong> — модульная CMS на <span class="font-medium">Laravel&nbsp;12.x</span> и <span class="font-medium">PHP&nbsp;8.5</span>
      с архитектурой HMVC, темизацией и редактором TinyMCE&nbsp;8. Эта страница — быстрый «onboarding» по структуре, модулям и расширению.
    </p>

    <div class="inline-flex flex-wrap items-center justify-center gap-2 text-xs text-gray-600">
      <span class="kbd">HMVC</span>
      <span class="kbd">Blade</span>
      <span class="kbd">Tailwind</span>
      <span class="kbd">TinyMCE&nbsp;8</span>
      <span class="kbd">Prism</span>
    </div>

    <div class="inline-flex items-center gap-3 text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-full px-3 py-1">
      <span class="inline-flex items-center gap-1">@themeIcon('calendar','w-3.5 h-3.5') Обновлено: {{ date('d.m.Y') }}</span>
      <span class="hidden sm:inline">•</span>
      <span class="inline-flex items-center gap-1">@themeIcon('github','w-3.5 h-3.5') Исходники: <a class="text-blue-700 hover:underline" target="_blank" rel="noopener" href="https://github.com/Bulavackii/Ru-CMS">GitHub</a></span>
    </div>
  </header>

  {{-- ОГЛАВЛЕНИЕ --}}
  <nav aria-label="Оглавление" class="no-print">
    <div class="card">
      <h2 class="text-sm font-semibold text-gray-700 mb-2">@themeIcon('list','w-4 h-4') Содержание</h2>
      <ol class="grid sm:grid-cols-2 gap-1 text-[14px] list-decimal pl-5">
        <li><a class="text-blue-700 hover:underline" href="#stack">Стек и требования</a></li>
        <li><a class="text-blue-700 hover:underline" href="#install">Быстрый старт / установка</a></li>
        <li><a class="text-blue-700 hover:underline" href="#structure">Структура проекта</a></li>
        <li><a class="text-blue-700 hover:underline" href="#module">Создание модуля</a></li>
        <li><a class="text-blue-700 hover:underline" href="#routes">Маршруты и шаблоны</a></li>
        <li><a class="text-blue-700 hover:underline" href="#theme">Темы и токены UI</a></li>
        <li><a class="text-blue-700 hover:underline" href="#editor">TinyMCE в контенте</a></li>
        <li><a class="text-blue-700 hover:underline" href="#security">Безопасность и валидация</a></li>
        <li><a class="text-blue-700 hover:underline" href="#contrib">Вклад и релизы</a></li>
      </ol>
    </div>
  </nav>

  {{-- 1. Стек и требования --}}
  <section id="stack" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">1. Стек и системные требования</h2>
    <ul class="list-disc pl-6">
      <li>PHP <strong>8.5</strong>, расширения: <code>mbstring</code>, <code>openssl</code>, <code>pdo</code>, <code>json</code>, <code>fileinfo</code>.</li>
      <li>СУБД: MySQL/MariaDB или PostgreSQL.</li>
      <li>Node.js для сборки фронтенда (Vite/Tailwind), Composer — для бэка.</li>
      <li>Веб-сервер nginx/Apache; очереди (redis) — по необходимости.</li>
    </ul>
  </section>

  {{-- 2. Быстрый старт --}}
  <section id="install" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">2. Быстрый старт / установка</h2>
    <p>Поддерживается установщик через <span class="font-medium">/install</span> (см. модуль Install).</p>
<pre class="language-bash"><code># 1) Клонируем репозиторий
git clone https://github.com/Bulavackii/Ru-CMS.git
cd Ru-CMS

# 2) Backend
composer install
cp .env.example .env
php artisan key:generate

# 3) Frontend
npm install
npm run build            # или npm run dev

# 4) Права и линк на публичные файлы
php artisan storage:link

# 5) Запускаем и проходим мастер установки
php artisan serve
# Открой http://127.0.0.1:8000/install
</code></pre>
    <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-lg p-3 text-sm">
      После мастера войдите в админ-панель, включите нужные модули и настройте тему.
    </div>
  </section>

  {{-- 3. Структура проекта --}}
  <section id="structure" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">3. Структура проекта (верхний уровень)</h2>
<pre class="language-none"><code>.
├─ app/
├─ config/
├─ modules/            ← автономные модули (HMVC)
│  ├─ News/
│  ├─ Categories/
│  ├─ Slideshow/
│  └─ ...
├─ public/
├─ resources/
│  ├─ views/           ← Blade-шаблоны
│  └─ css/js
├─ routes/
│  └─ web.php          ← сборная витрина/главная
└─ vendor/
</code></pre>
    <p class="text-sm text-gray-600">Каждый модуль в <code>modules/</code> автономен: свои контроллеры, роуты, миграции, модели, вьюшки, провайдер, <code>module.json</code>.</p>
  </section>

  {{-- 4. Создание модуля --}}
  <section id="module" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">4. Создание модуля (шаблон)</h2>
<pre class="language-none"><code>modules/
└─ Blog/
   ├─ module.json
   ├─ Providers/BlogServiceProvider.php
   ├─ Routes/web.php
   ├─ Http/Controllers/Admin/PostController.php
   ├─ Http/Controllers/Frontend/PostController.php
   ├─ Models/Post.php
   ├─ Resources/views/admin/*.blade.php
   └─ Resources/views/frontend/*.blade.php
</code></pre>

<p class="font-medium">module.json</p>
<pre class="language-json"><code>{
  "name": "Blog",
  "version": "1.0.0",
  "active": true,
  "providers": ["Modules\\Blog\\Providers\\BlogServiceProvider"]
}
</code></pre>

<p class="font-medium">BlogServiceProvider.php (кратко)</p>
<pre class="language-php"><code>&lt;?php

namespace Modules\Blog\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class BlogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'Blog');

        Route::middleware('web')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
</code></pre>

<p class="font-medium">Routes/web.php</p>
<pre class="language-php"><code>&lt;?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\Frontend\PostController;

Route::get('/blog', [PostController::class, 'index'])->name('blog.index');
</code></pre>

    <div class="bg-green-50 border border-green-200 text-green-900 rounded-lg p-3 text-sm">
      Модуль переносим между проектами без склейки с ядром.
    </div>
  </section>

  {{-- 5. Маршруты и шаблоны --}}
  <section id="routes" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">5. Маршруты, шаблоны и блоки главной</h2>
    <ul class="list-disc pl-6">
      <li>Главная собирает ленты по ключам в <code>$templateKeys</code> (<code>routes/web.php</code>), шаблоны секций лежат в <code>resources/views/frontend/templates/*</code>.</li>
      <li>Карточки/пагинация унифицированы; переименование «Новости → Наши услуги» делается в шаблоне секции (<code>ourworks.blade.php</code>).</li>
      <li>Каждая лента имеет свой <code>pageName</code> у пагинации для независимых страниц.</li>
    </ul>
  </section>

  {{-- 6. Темы и токены --}}
  <section id="theme" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">6. Темы и токены UI</h2>
    <ul class="list-disc pl-6">
      <li>CSS-переменные темы прокидываются из <code>layouts.frontend</code>: <code>--color-primary</code>, <code>--color-text</code>, <code>--color-bg</code>, <code>--radius-md</code> и др.</li>
      <li>Фон страницы — <code>--bg-image</code> из конфигурации активной темы.</li>
      <li>Режим иконок: <code>fa</code> / <code>bootstrap</code> / <code>remix</code> / <code>tabler</code> / <code>lucide</code>; вывод через <code>@themeIcon()</code>.</li>
    </ul>
  </section>

  {{-- 7. TinyMCE --}}
  <section id="editor" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">7. TinyMCE 8 в админ-редакторе</h2>
    <ul class="list-disc pl-6">
      <li>Загрузка медиа — через модуль файлов; публичные пути — <code>storage:link</code>.</li>
      <li>На фронте для превью чистим HTML: <code>Str::limit(strip_tags(...))</code>.</li>
      <li>Видео/обложки детектятся регулярками: <code>&lt;video&gt;</code>, <code>&lt;source&gt;</code>, <code>&lt;img&gt;</code>.</li>
    </ul>
  </section>

  {{-- 8. Безопасность --}}
  <section id="security" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">8. Безопасность и качество</h2>
    <ul class="list-disc pl-6">
      <li>Формы: <code>@csrf</code>, серверная валидация <code>FormRequest</code>, honeypot на публичных.</li>
      <li>Файлы: строгая валидация mime/размеров, хранение в <code>storage</code>.</li>
      <li>Доступ к админке: <code>auth</code> + <code>admin</code>.</li>
      <li>Код-стайл: PSR-12, типы, раздельные импорты, пагинация/кэш где уместно.</li>
    </ul>
  </section>

  {{-- 9. Вклад --}}
  <section id="contrib" class="section space-y-3">
    <h2 class="text-xl font-bold text-blue-700">9. Как поучаствовать</h2>
    <ol class="list-decimal pl-6 space-y-1">
      <li>Сделайте форк и отдельную ветку.</li>
      <li>Добавьте модуль/исправление + тест (где возможно).</li>
      <li>Откройте PR с описанием и скриншотами UI при визуальных изменениях.</li>
    </ol>

  </section>

  {{-- НИЗ --}}
  <p class="text-center text-sm text-gray-500">Последнее обновление: {{ date('d.m.Y') }}</p>

  <div class="text-center mt-6 no-print">
    <a href="{{ url('/') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow transition">
      ← На главную
    </a>
  </div>
</section>
@endsection
