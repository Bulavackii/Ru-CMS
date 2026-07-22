{{--
    22.07.2026: подвал перекомпонован — раньше три равные колонки без общей
    "шапки" секции. Теперь: акцентная полоса сверху (парная той, что в header,
    визуально скрепляет верх/низ страницы), брендовый ряд (лого + версия +
    реальный стек DB/Cache/Queue — эти значения раньше считались, но нигде не
    показывались) и закрывающая тонкая мета-полоса внизу вместо строки
    "Обновлено" в первой колонке. Прямые края — общий рубильник в
    layouts/admin.blade.php (.admin-sharp), здесь ничего дополнительно
    скруглять/срезать не нужно, кроме одного места ниже (rx у Rutube — это
    геометрия SVG, CSS border-radius на неё не действует).
--}}
<footer class="admin-glass mt-auto border-t text-sm text-gray-600 dark:text-gray-400">
<div class="admin-accent-bar" aria-hidden="true"></div>
@php
    use Illuminate\Support\Facades\Schema;

    $theme     = \Modules\Visual\Models\Theme::where('is_default', true)->first();
    $fontBase  = data_get($theme?->tokens,'font.base','-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');
    $iconMode  = data_get($theme?->config,'icon_mode','lucide');
    $iconsPath = rtrim((string) data_get($theme?->config,'icons_path',''),'/');

    /**
     * Универсальный вывод иконки.
     * — svg: если файла нет -> строгий фолбэк (стрелка вверх, чёрная).
     * — webfont наборы: используем небольшие карты соответствий. Если нет в карте -> фолбэк.
     * — fa: обычный рендер с поддержкой брендов.
     */
    $icon = function (string $name, string $cls='') use ($iconMode,$iconsPath) {
        $cls = trim($cls);

        // ---------- Кастомные бренд-глифы вне любых наборов иконок ----------
        // Ссылки внизу подвала (VK/MAX/Rutube/GitHub) всегда рисуются именно
        // этими SVG, а не через $maps[$iconMode] ниже — раньше VK, например,
        // при iconMode = lucide (дефолт темы) утекал в 'venetian-mask' (маска),
        // потому что близкой иконки VK в наборе Lucide нет. Теперь бренды в
        // подвале не зависят от выбранного набора иконок темы: VK и GitHub —
        // официальные глифы (Simple Icons, лицензия CC0), MAX и Rutube — свои,
        // обобщённые (эти сервисы не встречаются ни в одном открытом наборе
        // иконок, а точную копию логотипа копировать незачем).
        $customSvg = [
            'vk' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" class="inline-block align-middle '.e($cls).'"><path d="m9.489.004.729-.003h3.564l.73.003.914.01.433.007.418.011.403.014.388.016.374.021.36.025.345.03.333.033c1.74.196 2.933.616 3.833 1.516.9.9 1.32 2.092 1.516 3.833l.034.333.029.346.025.36.02.373.025.588.012.41.013.644.009.915.004.98-.001 3.313-.003.73-.01.914-.007.433-.011.418-.014.403-.016.388-.021.374-.025.36-.03.345-.033.333c-.196 1.74-.616 2.933-1.516 3.833-.9.9-2.092 1.32-3.833 1.516l-.333.034-.346.029-.36.025-.373.02-.588.025-.41.012-.644.013-.915.009-.98.004-3.313-.001-.73-.003-.914-.01-.433-.007-.418-.011-.403-.014-.388-.016-.374-.021-.36-.025-.345-.03-.333-.033c-1.74-.196-2.933-.616-3.833-1.516-.9-.9-1.32-2.092-1.516-3.833l-.034-.333-.029-.346-.025-.36-.02-.373-.025-.588-.012-.41-.013-.644-.009-.915-.004-.98.001-3.313.003-.73.01-.914.007-.433.011-.418.014-.403.016-.388.021-.374.025-.36.03-.345.033-.333c.196-1.74.616-2.933 1.516-3.833.9-.9 2.092-1.32 3.833-1.516l.333-.034.346-.029.36-.025.373-.02.588-.025.41-.012.644-.013.915-.009ZM6.79 7.3H4.05c.13 6.24 3.25 9.99 8.72 9.99h.31v-3.57c2.01.2 3.53 1.67 4.14 3.57h2.84c-.78-2.84-2.83-4.41-4.11-5.01 1.28-.74 3.08-2.54 3.51-4.98h-2.58c-.56 1.98-2.22 3.78-3.8 3.95V7.3H10.5v6.92c-1.6-.4-3.62-2.34-3.71-6.92Z"/></svg>',
            'github' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" class="inline-block align-middle '.e($cls).'"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
            'max' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" class="inline-block align-middle '.e($cls).'"><path d="M12 3C6.98 3 3 6.58 3 11c0 2.24 1.02 4.26 2.68 5.7-.12.98-.5 2.1-1.4 3.3-.16.2-.02.5.24.48 1.7-.12 3.28-.72 4.5-1.5A11.6 11.6 0 0 0 12 19c5.02 0 9-3.58 9-8s-3.98-8-9-8Z"/></svg>',
            'rutube' => '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="1.6" class="inline-block align-middle '.e($cls).'"><rect x="2" y="5" width="20" height="14" rx="0"/><path d="M10 8.7v6.6l5.7-3.3-5.7-3.3Z" fill="currentColor" stroke="none"/></svg>',
        ];
        if (isset($customSvg[$name])) {
            return $customSvg[$name];
        }

        // ---------- SVG ----------
        if ($iconMode === 'svg') {
            if ($iconsPath) {
                $path = public_path(ltrim(parse_url($iconsPath, PHP_URL_PATH) ?? '', '/').'/'.$name.'.svg');
                if (is_file($path)) {
                    $svg = @file_get_contents($path) ?: '';
                    if ($svg !== '') {
                        $svg = preg_replace('/<svg\b([^>]*)class="([^"]*)"/i', '<svg$1class="$2 '.e($cls).'"', $svg, 1, $count);
                        if (!$count) $svg = preg_replace('/<svg\b([^>]*)>/', '<svg$1 class="'.e($cls).'">', $svg, 1);
                        return $svg;
                    }
                }
            }
            return '<i class="fa-solid fa-arrow-up text-black '.e($cls).'"></i>';
        }

        // ---------- Карты соответствий для webfont-наборов ----------
        $maps = [
            'bootstrap' => [
                // системная сводка
                'cubes' => 'boxes', 'users' => 'people', 'newspaper' => 'newspaper', 'database' => 'database',
                'keyboard' => 'keyboard',
                // ссылки/соц
                'file-contract' => 'file-earmark-text', 'circle-question' => 'question-circle',
                'arrow-up' => 'arrow-up'
            ],
            'remix' => [
                'cubes' => 'apps-2', 'users' => 'team', 'newspaper' => 'newspaper', 'database' => 'database-2',
                'keyboard' => 'keyboard-box',
                'file-contract' => 'file-list-3', 'circle-question' => 'question-line',
                'arrow-up' => 'arrow-up-circle-line'
            ],
            'tabler' => [
                'cubes' => 'boxes', 'users' => 'users', 'newspaper' => 'news', 'database' => 'database',
                'keyboard' => 'keyboard',
                'file-contract' => 'file-description', 'circle-question' => 'help-circle',
                'arrow-up' => 'arrow-up'
            ],
            'lucide' => [
                'cubes' => 'boxes', 'users' => 'users', 'newspaper' => 'newspaper', 'database' => 'database',
                'keyboard' => 'keyboard',
                'file-contract' => 'file-text', 'circle-question' => 'circle-help',
                'arrow-up' => 'arrow-up'
            ],
        ];

        if (isset($maps[$iconMode])) {
            $mapped = $maps[$iconMode][$name] ?? null;
            if ($mapped) {
                return match ($iconMode) {
                    'bootstrap' => '<i class="bi bi-'.e($mapped).' '.e($cls).'"></i>',
                    'remix'     => '<i class="ri-'.e($mapped).' '.e($cls).'"></i>',
                    'tabler'    => '<i class="ti ti-'.e($mapped).' '.e($cls).'"></i>',
                    'lucide'    => '<i data-lucide="'.e($mapped).'" class="'.e($cls).'"></i>',
                };
            }
            // нет соответствия -> фолбэк
            return '<i class="fa-solid fa-arrow-up text-black '.e($cls).'"></i>';
        }

        // ---------- Font Awesome (софт-фолбэк брендов) ----------
        $brands = ['vk','telegram','whatsapp','github','youtube','facebook','x-twitter','twitter','instagram','linkedin','tiktok','discord'];
        $prefix = in_array($name, $brands, true) ? 'fa-brands' : 'fa-solid';
        return '<i class="'.$prefix.' fa-'.e($name).' '.e($cls).'"></i>';
    };

    // Статистика
    $stats = [];
    try { if (class_exists(\Modules\System\Models\Module::class) && Schema::hasTable('modules')) {
        $stats['modules'] = \Modules\System\Models\Module::where('active', 1)->count();
    }} catch (\Throwable $e) {}
    try { if (class_exists(\App\Models\User::class) && Schema::hasTable('users')) {
        $stats['users'] = \App\Models\User::count();
    }} catch (\Throwable $e) {}
    try { if (class_exists(\Modules\News\Models\News::class) && Schema::hasTable('news')) {
        $stats['news'] = \Modules\News\Models\News::count();
    }} catch (\Throwable $e) {}

    $drivers = [
        'DB'    => config('database.default'),
        'Cache' => config('cache.default'),
        'Queue' => config('queue.default'),
    ];

    // Ссылки (акцент — цвет подсветки нижней грани при наведении, не заливка
    // самой иконки: для MAX/Rutube это условный ориентир, а не сертифицированный
    // фирменный цвет бренда).
    $linkPills = [
        ['href'=>'https://vk.com/ru_cms',                'label'=>'VK',       'icon'=>'vk',     'accent'=>'#2563eb'],
        ['href'=>'#',                                    'label'=>'MAX',      'icon'=>'max',    'accent'=>'#7c3aed'],
        ['href'=>'#',                                    'label'=>'Rutube',   'icon'=>'rutube', 'accent'=>'#ea580c'],
        ['href'=>'https://github.com/Bulavackii/Ru-CMS', 'label'=>'GitHub',   'icon'=>'github', 'accent'=>'#111827'],
    ];
@endphp

<div class="max-w-screen-2xl mx-auto px-4 pt-6" style="font-family: {{ $fontBase }};">
    {{-- Брендовый ряд: лого + версия + реальный стек (DB/Cache/Queue — раньше
         эти значения считались, но нигде не показывались) --}}
    <div class="flex flex-wrap items-center gap-4 pb-5 border-b border-gray-200 dark:border-gray-800">
        <span class="admin-clip-corner flex-shrink-0 grid place-items-center w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-700 text-white shadow-md" aria-hidden="true">
            <i class="fas fa-layer-group"></i>
        </span>
        <div class="min-w-0">
            <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                <span class="font-bold text-gray-900 dark:text-white">RU CMS</span>
                <span class="text-xs text-gray-400">{{ config('app.version', '1.0.0') }}</span>
            </div>
            <p class="text-xs text-gray-500">Модульная CMS на Laravel</p>
        </div>

        <div class="ml-0 sm:ml-auto flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-mono text-gray-500">
            <span>PHP {{ PHP_VERSION }}</span>
            <span>Laravel {{ app()->version() }}</span>
            <span>DB: {{ $drivers['DB'] ?? '—' }}</span>
            <span>Cache: {{ $drivers['Cache'] ?? '—' }}</span>
            <span>Queue: {{ $drivers['Queue'] ?? '—' }}</span>
        </div>
    </div>

    <div class="py-6 grid gap-8 md:grid-cols-3 items-start">
        {{-- 1) Сводка --}}
        <section class="space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400">Статистика</h3>
            <ul class="grid gap-x-6 gap-y-1 sm:grid-cols-2 text-xs">
                <li class="flex items-center gap-2">{!! $icon('cubes','w-4 text-center') !!} Модулей: <strong>{{ $stats['modules'] ?? '—' }}</strong></li>
                <li class="flex items-center gap-2">{!! $icon('users','w-4 text-center') !!} Пользователей: <strong>{{ $stats['users'] ?? '—' }}</strong></li>
                <li class="flex items-center gap-2">{!! $icon('newspaper','w-4 text-center') !!} Новостей: <strong>{{ $stats['news'] ?? '—' }}</strong></li>
                <li class="flex items-center gap-2">{!! $icon('database','w-4 text-center') !!} {{ $drivers['DB'] ?? 'db' }}</li>
            </ul>
        </section>

        {{-- 2) Набор полезных ссылок + хоткеи --}}
        <section class="text-center md:text-left space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400">Быстрые ссылки</h3>

            <div class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-800/70 shadow-sm">
                {!! $icon('keyboard') !!}
                <span class="text-xs">Горячие клавиши:</span>
                <kbd class="px-1.5 py-0.5 border text-[11px] bg-gray-50 dark:bg-gray-900">Ctrl</kbd>
                <span class="text-[11px]">+</span>
                <kbd class="px-1.5 py-0.5 border text-[11px] bg-gray-50 dark:bg-gray-900">K</kbd>
                <span class="text-xs">— поиск</span>
            </div>

            <nav class="flex flex-wrap gap-3 justify-center md:justify-start">
                <a href="/terms" class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                   title="Условия использования">{!! $icon('file-contract','w-4 text-center') !!} Условия использования</a>
                <a href="https://github.com/Bulavackii/Ru-CMS" target="_blank"
                   class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                   title="GitHub проекта">{!! $icon('github','w-4 text-center') !!} GitHub проекта</a>
                <a href="/admin/help"
                   class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                   title="Поддержка и помощь">{!! $icon('circle-question','w-4 text-center') !!} Поддержка и помощь</a>
            </nav>
        </section>

        {{-- 3) Мы в сети: пилюли с цветной подсветкой нижней грани на hover --}}
        <section class="flex flex-col items-stretch gap-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 text-center md:text-right">Мы в сети</h3>

            <div class="flex flex-wrap gap-3 justify-center md:justify-end">
                @foreach($linkPills as $l)
                    <a href="{{ $l['href'] }}" target="_blank"
                       class="admin-social-pill inline-flex items-center gap-2 px-4 py-2 border-2 border-b-4 border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-800/70 hover:-translate-y-0.5 transition"
                       style="--accent: {{ $l['accent'] }}"
                       title="{{ $l['label'] }}" aria-label="{{ $l['label'] }}">
                        {!! $icon($l['icon']) !!}
                        <span class="font-medium">{{ $l['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="flex justify-center md:justify-end">
                <button type="button"
                        onclick="window.scrollTo({top:0,behavior:'smooth'})"
                        class="admin-clip-corner inline-flex items-center gap-2 px-3 py-1.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        title="Вверх" aria-label="Вверх">
                    {!! $icon('arrow-up') !!}
                    <span class="text-xs">Вверх</span>
                </button>
            </div>
        </section>
    </div>
</div>

<style>
    /* Подсветка нижней грани пилюли цветом бренда при наведении — цвет приходит
       через CSS-переменную --accent (инлайн-стиль на каждой ссылке), т.к.
       фиксированной Tailwind-утилиты под произвольный HEX в сборке нет. */
    .admin-social-pill:hover { border-bottom-color: var(--accent); }
</style>

{{-- Закрывающая мета-полоса --}}
<div class="border-t border-gray-200 dark:border-gray-800">
    <div class="max-w-screen-2xl mx-auto px-4 py-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
        <span>© {{ date('Y') }} RU CMS</span>
        <span id="admin-footer-time">Обновлено: <span class="font-mono">—</span></span>
    </div>
</div>

<script>
    (function () {
        const span = document.querySelector('#admin-footer-time span');
        if (span) {
            const now = new Date();
            try { span.textContent = now.toLocaleString('ru-RU', { dateStyle: 'medium', timeStyle: 'short' }); }
            catch (_) { span.textContent = now.toISOString().slice(0,16).replace('T',' '); }
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            try { window.lucide.createIcons(); } catch (e) {}
        }
    })();
</script>
</footer>
