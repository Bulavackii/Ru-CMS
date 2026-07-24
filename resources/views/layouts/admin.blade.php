<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Панель управления')</title>

  {{-- favicon.png в проекте нет — иконка лежит в SVG, как и на фронтенде --}}
  <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
  <link href="{{ local_css('tailwind.min.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="{{ local_css('font-awesome/all.min.css') }}" crossorigin="anonymous" referrerpolicy="no-referrer"/>
  <style>
    [x-cloak]{display:none!important}
    /* Утилиты Tailwind с /NN-прозрачностью (bg-white/80 и т.п.) отсутствуют в
       собранном public/assets/css/tailwind.min.css — это статическая сборка без
       JIT-сканирования содержимого, в неё вошли только полные стандартные
       классы, а не opacity-модификаторы и произвольные значения. Поэтому
       «стеклянные» полосы шапки/подвала админки задаём литеральным CSS. */
    .admin-glass{background:rgba(255,255,255,.82);backdrop-filter:blur(16px) saturate(160%);-webkit-backdrop-filter:blur(16px) saturate(160%)}
    .dark .admin-glass{background:rgba(17,24,39,.82)}
    /* Тёмный вариант — для единой шапки (header.blade.php), она всегда
       тёмная независимо от переключателя темы: под неё уже сделаны
       components.admin.global-search/notifications-center/dark-mode-toggle. */
    .admin-glass-dark{background:rgba(17,24,39,.9);backdrop-filter:blur(16px) saturate(160%);-webkit-backdrop-filter:blur(16px) saturate(160%)}

    /* Дизайн-язык админки: только прямые края, скруглений быть не должно нигде.
       Вместо правки rounded-* по десяткам вьюх и компонентов — один глобальный
       "рубильник", гарантированно перекрывающий и Tailwind-утилиты, и
       литеральный CSS (border-radius в собственном стек-стиле того же дашборда),
       т.к. !important побеждает специфичность независимо от порядка подключения.
       Область действия — только страницы админки (класс на <body> этого лейаута,
       фронтенд и письма его не подключают и не затрагиваются). */
    body.admin-sharp, body.admin-sharp * { border-radius: 0 !important; }

    /* Общие акценты шапки/подвала: полоса-градиент (визуально скрепляет верх
       и низ страницы) и срезанный угол вместо скругления — тот же "прямой,
       но не скучный" приём, что и у кнопки «Создать» в шапке. */
    .admin-accent-bar{height:3px;background:linear-gradient(90deg,#6366f1,#a855f7,#ec4899)}
    .admin-clip-corner{clip-path:polygon(0 0,100% 0,100% 100%,10px 100%,0 calc(100% - 10px))}

    /* Переиспользуемые утилиты админки (та же логика, что и .admin-glass:
       эффекты, которых нет в статической Tailwind-сборке, задаём литеральным
       CSS). Индиго-акцент — общий для дашборда/шапки/сайдбара редизайна.
       Литеральный .dark-селектор ЗДЕСЬ работает (класс .dark реально висит
       на <html>), в отличие от Tailwind dark:-утилит, которых в сборке нет. */
    .admin-icon-badge{display:inline-flex;align-items:center;justify-content:center;width:2.5rem;height:2.5rem;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;flex:none}
    .admin-card{background:#fff;border:1px solid #e5e7eb;transition:box-shadow .2s ease,transform .2s ease}
    .admin-card:hover{box-shadow:0 12px 24px -10px rgba(79,70,229,.28);transform:translateY(-2px)}
    .dark .admin-card{background:#1f2937;border-color:#374151}
    .admin-hint{border-left:3px solid #6366f1;background:#eef2ff;color:#312e81}
    .dark .admin-hint{background:rgba(99,102,241,.12);color:#c7d2fe}

    /* Рабочий тумблер. Tailwind-вариант peer-checked: в статической сборке
       ОТСУТСТВУЕТ (как dark:/opacity/arbitrary), из-за чего все тумблеры на
       peer-checked не переключались визуально. Здесь состояние берётся
       настоящим CSS-селектором input:checked ~ .track/.knob — он работает
       независимо от сборки Tailwind. */
    .admin-toggle{position:relative;display:inline-block;width:2.5rem;height:1.4rem;flex:none}
    .admin-toggle input{position:absolute;inset:0;width:100%;height:100%;opacity:0;margin:0;cursor:pointer;z-index:2}
    .admin-toggle .track{position:absolute;inset:0;background:#cbd5e1;transition:background .2s}
    .admin-toggle .knob{position:absolute;top:2px;left:2px;width:calc(1.4rem - 4px);height:calc(1.4rem - 4px);background:#fff;transition:left .2s;box-shadow:0 1px 2px rgba(0,0,0,.25);pointer-events:none}
    .admin-toggle input:checked ~ .track{background:#4f46e5}
    .admin-toggle input:checked ~ .knob{left:calc(100% - 1.4rem + 2px)}
    .dark .admin-toggle .track{background:#4b5563}
  </style>
  
  {{-- Vite для основного JS (Alpine и другие) --}}
  @vite(['resources/js/app.js'])

  {{-- Стек стилей для страниц с собственным точечным CSS (например, дашборда) --}}
  @stack('styles')

  {{-- Инициализация темы до загрузки Alpine.js (предотвращает мерцание) --}}
  {{-- Светлая тема всегда по умолчанию: системную dark-preference не учитываем, только явный выбор пользователя --}}
  <script>
    (function() {
      if (localStorage.getItem('darkMode') === 'true') {
        document.documentElement.classList.add('dark');
      }
    })();
  </script>
</head>

<body class="admin-sharp bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 transition-colors duration-200 min-h-screen">
  {{-- Мобильное меню --}}
  @include('layouts.admin.mobile-menu')
  
  {{-- фиксированный сайдбар --}}
  @include('layouts.admin.sidebar')

  {{-- каркас с липким верхом и обычным футером --}}
  <div id="admin-wrap" class="min-h-screen flex flex-col lg:pl-60 transition-all duration-300">

    {{-- ⬇️ Новый общий липкий контейнер для header + navbar --}}
    <div class="sticky top-0 z-50">
      @include('layouts.admin.header')
    </div>
    {{-- ⬆️ Конец липкого контейнера --}}

    <main class="flex-1 p-4 sm:p-6 md:p-8 lg:p-10 bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
      @include('layouts.partials.flash')
      @yield('content')
    </main>

    @include('layouts.admin.footer')
  </div>

  <script defer src="{{ local_js('alpine.min.js') }}"></script>
  <script src="{{ asset('js/admin/notifications.js') }}"></script>
  @stack('scripts')
  
  {{-- Обработка ошибок загрузки ресурсов --}}
  <script>
    (function() {
      // Проверка загрузки Alpine.js
      window.addEventListener('load', function() {
        if (typeof Alpine === 'undefined') {
          console.warn('Alpine.js не загружен. Проверьте путь к файлу.');
        }
      });
      
      // Обработка ошибок загрузки скриптов
      document.addEventListener('error', function(e) {
        if (e.target.tagName === 'SCRIPT') {
          console.error('Ошибка загрузки скрипта:', e.target.src);
        }
      }, true);
      
      // Обработка ошибок загрузки стилей
      document.addEventListener('error', function(e) {
        if (e.target.tagName === 'LINK' && e.target.rel === 'stylesheet') {
          console.error('Ошибка загрузки стилей:', e.target.href);
        }
      }, true);
    })();
  </script>

  <script>
    (function () {
      const sb = document.querySelector('aside');
      const wrap = document.getElementById('admin-wrap');
      function apply() {
        if (!sb || !wrap) return;
        const w = Math.round(sb.getBoundingClientRect().width);
        wrap.style.paddingLeft = w ? (w + 'px') : '';
      }
      if (window.ResizeObserver && sb) new ResizeObserver(apply).observe(sb);
      window.addEventListener('resize', apply, { passive: true });
      document.addEventListener('DOMContentLoaded', apply);
    })();
  </script>

  {{-- Финальный проход Lucide после полной загрузки DOM — подхватывает иконки из @yield('content') и всех компонентов --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        try { window.lucide.createIcons(); } catch (e) {}
      }
    });
  </script>
</body>
</html>
