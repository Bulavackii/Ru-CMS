<footer
    class="mt-auto border-t bg-white/95 dark:bg-gray-900/95 backdrop-blur text-sm text-gray-600 dark:text-gray-400"
>
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
                'github' => 'github', 'vk' => 'vk', 'telegram' => 'telegram', 'telegram-plane' => 'telegram',
                'whatsapp' => 'whatsapp', 'youtube' => 'youtube', 'arrow-up' => 'arrow-up'
            ],
            'remix' => [
                'cubes' => 'apps-2', 'users' => 'team', 'newspaper' => 'newspaper', 'database' => 'database-2',
                'keyboard' => 'keyboard-box',
                'file-contract' => 'file-list-3', 'circle-question' => 'question-line',
                'github' => 'github-fill', 'vk' => 'vkontakte-fill', 'telegram-plane' => 'telegram-line',
                'whatsapp' => 'whatsapp-fill', 'youtube' => 'youtube-fill', 'arrow-up' => 'arrow-up-circle-line'
            ],
            'tabler' => [
                'cubes' => 'boxes', 'users' => 'users', 'newspaper' => 'news', 'database' => 'database',
                'keyboard' => 'keyboard',
                'file-contract' => 'file-description', 'circle-question' => 'help-circle',
                'github' => 'brand-github', 'vk' => 'brand-vk', 'telegram-plane' => 'brand-telegram',
                'whatsapp' => 'brand-whatsapp', 'youtube' => 'brand-youtube', 'arrow-up' => 'arrow-up'
            ],
            'lucide' => [
                'cubes' => 'boxes', 'users' => 'users', 'newspaper' => 'newspaper', 'database' => 'database',
                'keyboard' => 'keyboard',
                'file-contract' => 'file-text', 'circle-question' => 'circle-help',
                'github' => 'github', 'vk' => 'venetian-mask', /* близкой иконки VK в lucide нет */
                'telegram-plane' => 'send', 'whatsapp' => 'message-circle', 'youtube' => 'youtube',
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
        $aliases = ['telegram-plane' => 'telegram'];
        $fa = $aliases[$name] ?? $name;
        $brands = ['vk','telegram','whatsapp','github','youtube','facebook','x-twitter','twitter','instagram','linkedin','tiktok','discord'];
        $prefix = in_array($fa, $brands, true) ? 'fa-brands' : 'fa-solid';
        return '<i class="'.$prefix.' fa-'.e($fa).' '.e($cls).'"></i>';
    };

    $env = app()->environment();
    $envCls = match ($env) {
        'production' => 'bg-emerald-600',
        'staging'    => 'bg-amber-500',
        'testing'    => 'bg-blue-600',
        default      => 'bg-rose-600',
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

    // Ссылки (пилюли переменной ширины — как на фронте)
    $linkPills = [
        ['href'=>'https://vk.com/ru_cms',                'label'=>'VK',        'icon'=>'vk'],
        ['href'=>'https://t.me/ru_cms',                  'label'=>'Telegram',  'icon'=>'telegram-plane'],
        ['href'=>'https://wa.me/79856204400',            'label'=>'WhatsApp',  'icon'=>'whatsapp'],
        ['href'=>'https://github.com/Bulavackii/Ru-CMS', 'label'=>'GitHub',    'icon'=>'github'],
        ['href'=>'#',                                    'label'=>'YouTube',   'icon'=>'youtube'],
    ];
@endphp

<div class="max-w-screen-2xl mx-auto px-4 py-6 grid gap-8 md:grid-cols-3 items-start" style="font-family: {{ $fontBase }};">
    {{-- 1) Сводка (с фолбэком иконок) --}}
    <section class="space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <span class="px-2 py-0.5 rounded-full text-[11px] text-white {{ $envCls }}">{{ strtoupper($env) }}</span>
            <span class="text-xs">v1.0.0</span>
            <span class="text-xs">· PHP {{ PHP_VERSION }}</span>
            <span class="text-xs">· Laravel {{ app()->version() }}</span>
        </div>

        <ul class="grid gap-x-6 gap-y-1 sm:grid-cols-2 text-xs">
            <li class="flex items-center gap-2">{!! $icon('cubes','w-4 text-center') !!} Модулей: <strong>{{ $stats['modules'] ?? '—' }}</strong></li>
            <li class="flex items-center gap-2">{!! $icon('users','w-4 text-center') !!} Пользователей: <strong>{{ $stats['users'] ?? '—' }}</strong></li>
            <li class="flex items-center gap-2">{!! $icon('newspaper','w-4 text-center') !!} Новостей: <strong>{{ $stats['news'] ?? '—' }}</strong></li>
            <li class="flex items-center gap-2">{!! $icon('database','w-4 text-center') !!} {{ $drivers['DB'] ?? 'db' }}</li>
        </ul>

        <div class="text-xs text-gray-500" id="admin-footer-time">
            Обновлено: <span class="font-mono">—</span>
        </div>
    </section>

    {{-- 2) Набор полезных ссылок + хоткеи --}}
    <section class="text-center md:text-left">
        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-800/70 shadow-sm">
            {!! $icon('keyboard') !!}
            <span class="text-xs">Горячие клавиши:</span>
            <kbd class="px-1.5 py-0.5 rounded border text-[11px] bg-gray-50 dark:bg-gray-900">Ctrl</kbd>
            <span class="text-[11px]">+</span>
            <kbd class="px-1.5 py-0.5 rounded border text-[11px] bg-gray-50 dark:bg-gray-900">K</kbd>
            <span class="text-xs">— поиск</span>
        </div>

        <nav class="mt-3 flex flex-wrap gap-3">
            <a href="/terms" class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
               title="Условия использования">{!! $icon('file-contract','w-4 text-center') !!} Условия использования</a>
            <a href="https://github.com/Bulavackii/Ru-CMS" target="_blank"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
               title="GitHub проекта">{!! $icon('github','w-4 text-center') !!} GitHub проекта</a>
            <a href="/admin/help"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
               title="Поддержка и помощь">{!! $icon('circle-question','w-4 text-center') !!} Поддержка и помощь</a>
        </nav>
    </section>

    {{-- 3) Ссылки (как на фронте): пилюли разной ширины + «Вверх» --}}
    <section class="flex flex-col items-stretch gap-3">
        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100 text-center md:text-right">Ссылки</h3>

        <div class="flex flex-wrap gap-3 justify-center md:justify-end">
            @foreach($linkPills as $l)
                <a href="{{ $l['href'] }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-800/70 hover:shadow-sm hover:-translate-y-0.5 transition"
                   title="{{ $l['label'] }}" aria-label="{{ $l['label'] }}">
                    {!! $icon($l['icon']) !!}
                    <span class="font-medium">{{ $l['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="flex justify-end">
            <button type="button"
                    onclick="window.scrollTo({top:0,behavior:'smooth'})"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    title="Вверх" aria-label="Вверх">
                {!! $icon('arrow-up') !!}
                <span class="text-xs">Вверх</span>
            </button>
        </div>
    </section>
</div>

<style>
    /* слегка увеличим иконки в пилюлях для выразительности */
    .social-links a { font-size: 28px; line-height: 1; }
    @media (min-width: 768px) { .social-links a { font-size: 32px; } }
</style>

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
