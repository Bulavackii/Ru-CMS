{{--
    Подвал панели.

    26.07.2026: перекомпонован под ту же логику, что и шапка — плотная полоса
    вместо трёх колонок с пустотами. Что убрано и почему:

    — Колонка «Быстрые ссылки». «Условия использования» и «GitHub проекта»
      дублировали ссылки из других мест, а «Поддержка и помощь» вела на
      /admin/help — такого маршрута в проекте НЕТ (проверено route:list),
      то есть кнопка была мёртвой с самого начала.
    — Кнопка «Вверх»: занимала отдельный блок ради того, что делают колесо
      мыши и клавиша Home.
    — Подпись «Обновлено: <текущее время>» внизу. Она печатала момент
      отрисовки страницы — то есть всегда «сейчас» — и не значила ничего.
      Вместо неё честная дата установки CMS (mtime storage/install.lock).
    — Ручные SVG-глифы MAX и Rutube. Точных логотипов этих сервисов нет ни в
      одном открытом наборе иконок, и рисунки были самодельными «похожими».
      Теперь весь ряд соцсетей — настоящие брендовые глифы Font Awesome
      (fa-vk / fa-telegram / fa-whatsapp / fa-github, все есть в локальной
      сборке), с фирменным цветом через переменную --c, как в подвале сайта
      (layouts/partials/footer) — подвалы панели и сайта выглядят роднёй.
    — Закрытая функция $icon на 90 строк с картами соответствий под каждый
      набор иконок темы. Здесь нужны четыре крошечных значка в строке
      статистики; Font Awesome в панели подключён всегда (layouts/admin),
      а этот же файл и раньше рисовал логотип прямо через fas fa-layer-group.

    Версия проекта переехала сюда из подвала сайдбара: раньше она была в двух
    местах, теперь в одном — рядом со стеком, которому и принадлежит.
--}}
{{-- 🧩 Зона фрагмента: блок над подвалом панели («Памятка редактора») --}}
@php $fragmentAdminFooter = \Modules\Visual\Support\FragmentRenderer::zone('admin.footer'); @endphp
@if($fragmentAdminFooter)
    <div class="fragment-zone fragment-zone--admin-footer">{!! $fragmentAdminFooter !!}</div>
@endif

@php
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\Schema;

    $fontBase = data_get(($activeTheme ?? null)?->tokens, 'font.base', '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');

    // Сводка по содержимому. Каждый запрос под своим try/catch: подвал не
    // должен ронять страницу, если модуль отключён или таблицы ещё нет.
    $stats = [];
    try { if (class_exists(\Modules\System\Models\Module::class) && Schema::hasTable('modules')) {
        $stats['modules'] = \Modules\System\Models\Module::where('active', 1)->count();
    }} catch (\Throwable $e) {}
    try { if (Schema::hasTable('users')) {
        $stats['users'] = \App\Models\User::count();
    }} catch (\Throwable $e) {}
    try { if (class_exists(\Modules\News\Models\News::class) && Schema::hasTable('news')) {
        $stats['news'] = \Modules\News\Models\News::count();
    }} catch (\Throwable $e) {}

    // Объём медиатеки. Обход каталога — не та работа, которую можно делать на
    // КАЖДОЙ странице панели, поэтому результат кешируется на час.
    $mediaSize = Cache::remember('admin_footer_media_size', 3600, function () {
        $root = storage_path('app/public');
        if (! is_dir($root)) {
            return null;
        }
        $bytes = 0;
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $bytes += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            return null;
        }
        return $bytes;
    });

    $formatSize = function (?int $bytes): string {
        if ($bytes === null) {
            return '—';
        }
        $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $i > 1 ? 1 : 0, ',', ' ') . ' ' . $units[$i];
    };

    // Дата установки: реальный mtime lock-файла, который кладёт мастер установки
    $installedAt = null;
    try {
        $lock = storage_path('install.lock');
        if (is_file($lock) && ($ts = @filemtime($lock))) {
            $installedAt = \Illuminate\Support\Carbon::createFromTimestamp($ts);
        }
    } catch (\Throwable $e) {}

    $debugOn = (bool) config('app.debug');

    // Сколько система работает. Одна дата установки отвечает «когда», но не
    // «давно ли» — а на глаз это считает не каждый.
    $uptimeDays = $installedAt ? (int) $installedAt->startOfDay()->diffInDays(now()->startOfDay()) : null;

    // Показатели ЭТОГО запроса. Верхний ряд перечисляет настройки, которые
    // не меняются неделями; здесь — то, что у каждой страницы своё, и по чему
    // сразу видно подтормаживающий раздел.
    //
    // Отсчёт от LARAVEL_START, который ставит public/index.php. В консоли и
    // тестах входной точки нет, поэтому константы может не быть.
    $renderMs = defined('LARAVEL_START')
        ? (int) round((microtime(true) - LARAVEL_START) * 1000)
        : null;

    // Пиковое потребление, а не текущее: важен максимум, до которого запрос
    // доходил, иначе цифра ничего не говорит о запасе до memory_limit.
    $memoryPeak = memory_get_peak_usage(true);

    // Ряд технических значений. Всё берётся из конфигурации как есть —
    // ничего не додумываем: чего нет, того и не показываем.
    $tech = [
        ['label' => 'PHP',                              'value' => PHP_VERSION],
        ['label' => 'Laravel',                          'value' => app()->version()],
        ['label' => __('admin.footer.db'),              'value' => config('database.default')],
        ['label' => __('admin.footer.cache'),           'value' => config('cache.default')],
        ['label' => __('admin.footer.queue'),           'value' => config('queue.default')],
        ['label' => __('admin.footer.environment'),     'value' => app()->environment()],
        ['label' => __('admin.footer.locale'),          'value' => app()->getLocale()],
    ];

    // Один список на подвал сайта и подвал панели (см. social_links() в
    // app/helpers.php). Раньше он был записан в обоих шаблонах, и адреса
    // успели разойтись: здесь стоял vk.com/ru_cms, на сайте vk.com/example.
    $socials = social_links();
@endphp

<footer class="admin-glass mt-auto border-t border-gray-200 dark:border-gray-800 text-sm text-gray-600 dark:text-gray-400"
        style="font-family: {{ $fontBase }};">
    <div class="admin-accent-bar" aria-hidden="true"></div>

    <div class="w-full px-4 py-3 flex flex-wrap items-center gap-x-5 gap-y-3">

        {{-- Бренд и версия --}}
        <div class="flex items-center gap-2.5 flex-none">
            {{-- Значок в цвете активного оформления, а не прибитый indigo:
                 подвал должен меняться вместе с темой, как шапка и сайдбар --}}
            <span class="admin-clip-corner adm-f-logo" aria-hidden="true">
                <i class="fas fa-layer-group text-xs"></i>
            </span>
            <span class="leading-tight">
                <span class="flex items-baseline gap-1.5">
                    <span class="font-bold text-gray-900 dark:text-white">RU CMS</span>
                    <span class="adm-f-ver">{{ config('app.version', '1.0.0') }}</span>
                </span>
                <span class="block text-xs text-gray-500">{{ __('admin.footer.tagline') }}</span>
            </span>
        </div>

        {{-- Техника --}}
        <div class="adm-f-set" role="group" aria-label="{{ __('admin.footer.tech') }}">
            @foreach($tech as $item)
                <span class="adm-f-chip">
                    <span class="adm-f-key">{{ $item['label'] }}</span>{{ $item['value'] }}
                </span>
            @endforeach
            {{-- Отладку выделяем: включённой на боевом сервере быть не должно --}}
            <span class="adm-f-chip {{ $debugOn ? 'is-warn' : '' }}">
                <span class="adm-f-key">{{ __('admin.footer.debug') }}</span>
                {{ $debugOn ? __('admin.footer.debug_on') : __('admin.footer.debug_off') }}
            </span>
        </div>

        {{-- Содержимое --}}
        <div class="adm-f-set" role="group" aria-label="{{ __('admin.footer.content') }}">
            <span class="adm-f-chip"><i class="fas fa-cubes adm-f-ico"></i>
                <span class="adm-f-key">{{ __('admin.footer.modules') }}</span>{{ $stats['modules'] ?? '—' }}</span>
            <span class="adm-f-chip"><i class="fas fa-users adm-f-ico"></i>
                <span class="adm-f-key">{{ __('admin.footer.users') }}</span>{{ $stats['users'] ?? '—' }}</span>
            <span class="adm-f-chip"><i class="fas fa-newspaper adm-f-ico"></i>
                <span class="adm-f-key">{{ __('admin.footer.news') }}</span>{{ $stats['news'] ?? '—' }}</span>
            <span class="adm-f-chip" title="{{ __('admin.footer.media_hint') }}"><i class="fas fa-photo-film adm-f-ico"></i>
                <span class="adm-f-key">{{ __('admin.footer.media') }}</span>{{ $formatSize($mediaSize) }}</span>
        </div>

    </div>

    {{-- Нижняя полоса: слева — про саму систему, в середине — что с сервером
         прямо сейчас, справа — сети.

         Раньше здесь стояли копирайт и четыре значка, а между ними полтора
         экрана пустоты. Заполнена она не «чем-нибудь», а тем, чего в верхнем
         ряду нет по существу: тот перечисляет настройки, которые не меняются
         неделями, а часы, время отрисовки и память — величины этой самой
         страницы. Пояс переехал к часам: показывать его отдельным чипом рядом
         со временем в том же поясе — повтор.

         Сети живут здесь, а не в верхнем ряду: там чипы ровно занимают строку,
         и значки всё равно переносились на отдельную, оставляя под собой
         пустую половину. --}}
    <div class="border-t border-gray-200 dark:border-gray-800">
        <div class="w-full px-4 py-1.5 adm-f-meta text-xs text-gray-500">

            {{-- Про систему --}}
            <div class="adm-f-grp">
                <span>© {{ date('Y') }} RU CMS</span>

                <span class="adm-f-dot" aria-hidden="true">·</span>
                <span>
                    {{ __('admin.footer.developer') }}
                    Булавацкий Д.О.
                </span>



                @if($installedAt)
                    <span class="adm-f-dot" aria-hidden="true">·</span>
                    <span>
                        {{ __('admin.footer.installed') }}
                        <span class="font-mono">{{ $installedAt->translatedFormat('d.m.Y') }}</span>
                        <span class="adm-f-mut">({{ trans_choice('admin.footer.uptime_days', $uptimeDays, ['count' => $uptimeDays]) }})</span>
                    </span>
                @endif
            </div>

            {{-- Про этот запрос --}}
            <div class="adm-f-grp adm-f-mid">
                <span class="adm-f-live" title="{{ __('admin.footer.server_time') }} · {{ __('admin.footer.timezone') }}: {{ config('app.timezone') }}">
                    <i class="fas fa-clock adm-f-ico"></i>
                    {{-- Часы идут: застывшее время отрисовки — ровно та подпись
                         «Обновлено: сейчас», которую отсюда однажды уже убрали
                         за то, что она не значила ничего. Сдвиг пояса приходит
                         числом, чтобы показывать время СЕРВЕРА, а не браузера
                         (у администратора он бывает другим). --}}
                    <time class="adm-f-clock font-mono"
                          data-epoch="{{ now()->getTimestamp() }}"
                          data-offset="{{ now()->utcOffset() * 60 }}">{{ now()->format('H:i:s') }}</time>
                    <span class="adm-f-mut">{{ config('app.timezone') }}</span>
                </span>

                @if($renderMs !== null)
                    <span class="adm-f-dot" aria-hidden="true">·</span>
                    <span class="adm-f-live" title="{{ __('admin.footer.render_hint') }}">
                        <i class="fas fa-bolt adm-f-ico"></i>
                        <span class="adm-f-key">{{ __('admin.footer.render') }}</span>
                        <span class="font-mono">{{ $renderMs }} {{ __('admin.footer.ms') }}</span>
                    </span>
                @endif

                <span class="adm-f-dot" aria-hidden="true">·</span>
                <span class="adm-f-live" title="{{ __('admin.footer.memory_hint') }}">
                    <i class="fas fa-microchip adm-f-ico"></i>
                    <span class="adm-f-key">{{ __('admin.footer.memory') }}</span>
                    <span class="font-mono">{{ $formatSize($memoryPeak) }}</span>
                </span>
            </div>

            {{-- Сети --}}
            @if($socials)
                <div class="adm-f-grp adm-f-end">
                    <span class="adm-f-soc-label">{{ __('admin.footer.socials') }}</span>
                    @foreach($socials as $social)
                        <a href="{{ $social['href'] }}" target="_blank" rel="noopener"
                           class="adm-f-social" style="--c: {{ $social['color'] }}"
                           title="{{ $social['label'] }}" aria-label="{{ $social['label'] }}">
                            {{-- Глифов MAX и Rutube нет ни в одном открытом
                                 наборе — для них свои SVG-компоненты. --}}
                            @switch($social['key'])
                                @case('max')    <x-icon.max :size="16" /> @break
                                @case('rutube') <x-icon.rutube :size="16" /> @break
                                @case('vk')     <x-icon.vk :size="16" /> @break
                                @case('github') <x-icon.github :size="16" /> @break
                            @endswitch
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <style>
        /* Литеральный CSS — в собранном tailwind.min.css нет ни
           opacity-модификаторов, ни произвольных значений, ни dark:-вариантов
           (см. CLAUDE.md), поэтому мелкую типографику подвала задаём здесь. */
        .adm-f-logo{display:grid;place-items:center;width:2rem;height:2rem;flex:none;
            color:var(--admin-on-primary,#fff);box-shadow:0 4px 10px -6px var(--admin-primary-glow,rgba(79,70,229,.5));
            background:linear-gradient(135deg,var(--admin-primary,#6366f1),var(--admin-accent,#a855f7))}
        .adm-f-ver{padding:.05rem .3rem;font-size:.62rem;font-weight:700;letter-spacing:.03em;
            color:var(--admin-on-primary,#fff);background:var(--admin-primary,#6366f1)}
        /* flex:0 1 auto + min-width:0 — наборы чипов СЖИМАЮТСЯ и переносятся
           внутри себя, но не растягиваются: с flex-grow они забирали всю
           строку и выталкивали соцсети на вторую */
        .adm-f-set{display:flex;flex-wrap:wrap;align-items:center;gap:.3rem;flex:0 1 auto;min-width:0}
        .adm-f-chip{display:inline-flex;align-items:center;gap:.3rem;padding:.15rem .45rem;
            font-size:.68rem;line-height:1.3;white-space:nowrap;color:var(--surface-ink,#4b5563);
            background:rgba(17,24,39,.04);border:1px solid rgba(17,24,39,.07);
            font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
        .dark .adm-f-chip{color:#9ca3af;background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.08)}
        .adm-f-key{color:var(--surface-dim,#9ca3af);font-weight:600}
        .adm-f-ico{font-size:.7rem;color:var(--admin-primary,#6366f1)}
        /* Включённая отладка — не украшение, а предупреждение */
        .adm-f-chip.is-warn{color:#92400e;background:#fef3c7;border-color:#fcd34d}
        .dark .adm-f-chip.is-warn{color:#fcd34d;background:rgba(146,64,14,.25);border-color:#92400e}

        /* Соцсети: тот же приём, что в подвале сайта — фирменный цвет приходит
           переменной --c и проявляется при наведении */
        .adm-f-social{display:grid;place-items:center;width:1.9rem;height:1.9rem;flex:none;
            color:var(--surface-mute,#6b7280);border:1px solid rgba(17,24,39,.1);
            transition:color .15s ease,border-color .15s ease,background .15s ease}
        .dark .adm-f-social{color:#9ca3af;border-color:rgba(255,255,255,.1)}
        .adm-f-social:hover{color:var(--c,#6366f1);border-color:var(--c,#6366f1);
            background:rgba(17,24,39,.03)}
        .dark .adm-f-social:hover{background:rgba(255,255,255,.06)}

        .adm-f-link{color:var(--admin-primary,#4f46e5);font-weight:500}
        .adm-f-link:hover{text-decoration:underline}
        .adm-f-dot{opacity:.45}
        .adm-f-mut{color:var(--surface-dim,#9ca3af)}

        /* Три полосы содержимого в одной строке: по краям — сколько нужно,
           середина забирает остаток и встаёт по центру. Сеткой, а не flex:
           при переносе flex-строки её остаток нечем прижать, и правый край
           уезжает (тот же случай, что описан в CLAUDE.md про подвал сайта). */
        .adm-f-meta{display:grid;grid-template-columns:auto 1fr auto;
            align-items:center;gap:.35rem 1.25rem}
        .adm-f-grp{display:flex;flex-wrap:wrap;align-items:center;gap:.35rem .5rem;min-width:0}
        .adm-f-mid{justify-self:center}
        .adm-f-end{justify-self:end}
        /* Ниже 1280px три полосы в строку не помещаются — раскладываем
           столбцом по центру, ничего не пряча. */
        @media (max-width:1279px){
            .adm-f-meta{grid-template-columns:1fr;justify-items:center;gap:.4rem}
            .adm-f-grp{justify-content:center}
            .adm-f-mid,.adm-f-end{justify-self:center}
        }

        .adm-f-live{display:inline-flex;align-items:center;gap:.3rem;white-space:nowrap}
        .adm-f-clock{color:var(--surface-ink,#4b5563);font-weight:600;font-variant-numeric:tabular-nums}
        .dark .adm-f-clock{color:#d1d5db}

        .adm-f-soc-label{margin-right:.15rem;font-size:.6rem;font-weight:700;
            letter-spacing:.08em;text-transform:uppercase;color:var(--surface-dim,#9ca3af)}
    </style>

    <script>
        /* Часы подвала. Время берём с сервера (epoch + его сдвиг от UTC) и
           дальше идём сами: перезапрашивать страницу ради секундной стрелки
           незачем, а часы браузера могут показывать другой пояс.
           Сдвиг применяем к метке времени и читаем её через getUTC* — так
           выходит стенное время сервера независимо от настроек машины. */
        (function () {
            var el = document.querySelector('.adm-f-clock');
            if (!el) return;

            var base = parseInt(el.dataset.epoch, 10) + parseInt(el.dataset.offset, 10);
            if (isNaN(base)) return;

            var started = Date.now();
            var pad = function (n) { return n < 10 ? '0' + n : '' + n; };

            setInterval(function () {
                var d = new Date((base + Math.floor((Date.now() - started) / 1000)) * 1000);
                el.textContent = pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes()) + ':' + pad(d.getUTCSeconds());
            }, 1000);
        })();
    </script>
</footer>
