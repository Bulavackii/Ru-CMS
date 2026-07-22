@extends('layouts.admin')

@section('title', 'Панель управления')

@section('content')
<div class="dash space-y-6" x-data="dashGreeting()">

    {{-- ═══════════════════════ Приветственный блок ═══════════════════════ --}}
    <div class="dash-hero rounded-3xl px-6 py-7 sm:px-9 sm:py-8">
        <span class="dash-aurora dash-aurora--a" aria-hidden="true"></span>
        <span class="dash-aurora dash-aurora--b" aria-hidden="true"></span>

        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">
                    <i class="fas" :class="icon"></i>
                    <span x-text="greeting">Добро пожаловать</span>
                </div>
                <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white break-words">
                    {{ auth()->user()->name }} <span aria-hidden="true">👋</span>
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 capitalize" x-text="dateLabel"></p>
            </div>

            {{-- Быстрое создание: одна основная кнопка + пилюли-акценты --}}
            <div class="flex flex-wrap items-center gap-2">
                @foreach($quickActions as $i => $action)
                    <a href="{{ $action['url'] }}"
                       class="dash-pill {{ $i === 0 ? 'dash-pill--primary' : 'dash-pill--' . $action['color'] }} inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold">
                        <i class="fas fa-{{ $action['icon'] }}"></i>
                        <span class="{{ $i === 0 ? '' : 'hidden xl:inline' }}">{{ $action['title'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="relative z-10 mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-gray-200 pt-4 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
            <span class="inline-flex items-center gap-1.5">
                <i class="fas fa-clock hint-ico"></i>
                <span class="font-mono" x-text="clock"></span>
            </span>
            <span class="inline-flex items-center gap-1.5">
                <i class="fas fa-bolt hint-ico"></i>
                Статистика обновляется раз в 5 минут
            </span>
            @if($licenseInfo && ($licenseInfo['is_expiring_soon'] || $licenseInfo['is_expired']))
                <a href="{{ route('admin.subscriptions.index') }}" class="inline-flex items-center gap-1.5 font-semibold text-red-600 dark:text-red-400 hover:underline">
                    <i class="fas fa-triangle-exclamation"></i>
                    Лицензия {{ $licenseInfo['is_expired'] ? 'истекла' : 'скоро истекает' }}
                </a>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════ Карточки статистики ═══════════════════════ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('admin.news.index') }}" class="dash-stat dash-stat--blue group">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Новости</p>
                    <p class="dash-counter mt-1 text-3xl font-extrabold text-gray-900 dark:text-white" data-target="{{ $stats['content']['news']['total'] }}">0</p>
                </div>
                <span class="dash-badge dash-badge--blue"><i class="fas fa-newspaper"></i></span>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="dash-chip dash-chip--blue">+{{ $stats['content']['news']['this_week'] }} за неделю</span>
                <span class="text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-gray-600 dark:group-hover:text-gray-300">
                    {{ $stats['content']['news']['published'] }} опубл. <i class="fas fa-arrow-right ml-0.5"></i>
                </span>
            </div>
        </a>

        <a href="{{ route('admin.pages.index') }}" class="dash-stat dash-stat--green group">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Страницы</p>
                    <p class="dash-counter mt-1 text-3xl font-extrabold text-gray-900 dark:text-white" data-target="{{ $stats['content']['pages']['total'] }}">0</p>
                </div>
                <span class="dash-badge dash-badge--green"><i class="fas fa-file-lines"></i></span>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="dash-chip dash-chip--green">{{ $stats['content']['pages']['published'] }} опубликовано</span>
                <span class="text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-gray-600 dark:group-hover:text-gray-300">
                    Все страницы <i class="fas fa-arrow-right ml-0.5"></i>
                </span>
            </div>
        </a>

        <a href="{{ route('admin.users.index') }}" class="dash-stat dash-stat--purple group">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Пользователи</p>
                    <p class="dash-counter mt-1 text-3xl font-extrabold text-gray-900 dark:text-white" data-target="{{ $stats['users']['total'] }}">0</p>
                </div>
                <span class="dash-badge dash-badge--purple"><i class="fas fa-users"></i></span>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs">
                <span class="dash-chip dash-chip--purple">+{{ $stats['users']['this_week'] }} за неделю</span>
                <span class="text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-gray-600 dark:group-hover:text-gray-300">
                    {{ $stats['users']['admins'] }} админ. <i class="fas fa-arrow-right ml-0.5"></i>
                </span>
            </div>
        </a>

        @if(isset($stats['orders']))
            <a href="{{ route('admin.orders.index') }}" class="dash-stat dash-stat--orange group">
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Заказы</p>
                        <p class="dash-counter mt-1 text-3xl font-extrabold text-gray-900 dark:text-white" data-target="{{ $stats['orders']['total'] }}">0</p>
                    </div>
                    <span class="dash-badge dash-badge--orange"><i class="fas fa-cart-shopping"></i></span>
                </div>
                <div class="mt-4 flex items-center justify-between text-xs">
                    <span class="dash-chip dash-chip--orange">{{ $stats['orders']['pending'] }} ожидают</span>
                    <span class="text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-gray-600 dark:group-hover:text-gray-300">
                        {{ number_format($stats['orders']['revenue'], 0, ',', ' ') }} ₽/мес <i class="fas fa-arrow-right ml-0.5"></i>
                    </span>
                </div>
            </a>
        @endif
    </div>

    {{-- ═══════════════════════ График активности ═══════════════════════ --}}
    <div class="dash-card p-6">
        <div class="mb-4 flex items-center gap-3">
            <span class="dash-badge dash-badge--indigo dash-badge--sm"><i class="fas fa-chart-line"></i></span>
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Активность за последние 7 дней</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Новости, пользователи и заказы день за днём</p>
            </div>
        </div>
        <div class="h-72">
            <canvas id="activityChart"></canvas>
        </div>
    </div>

    {{-- ═══════════════════════ Виджеты (перетаскиваются) ═══════════════════════ --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3" id="dashboard-widgets">

        {{-- Последняя активность --}}
        <div class="dash-card lg:col-span-2 p-6" data-widget-id="activity">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="dash-badge dash-badge--gray dash-badge--sm"><i class="fas fa-list-check"></i></span>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Последняя активность</h2>
                </div>
                <div class="widget-handle cursor-move text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400" title="Перетащить">
                    <i class="fas fa-grip-vertical"></i>
                </div>
            </div>
            <div class="space-y-1.5">
                @forelse($recentActivity as $activity)
                    <a href="{{ $activity['url'] }}" class="dash-activity-row flex items-center gap-3 rounded-xl p-2.5">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <i class="fas fa-{{ $activity['icon'] }}"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $activity['title'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activity['user'] }} · {{ $activity['time'] }}</p>
                        </div>
                    </a>
                @empty
                    <p class="py-10 text-center text-sm text-gray-400">Активность пока отсутствует</p>
                @endforelse
            </div>
        </div>

        {{-- Лицензия --}}
        @if($licenseInfo)
            @php
                $licenseColor = match(true) {
                    $licenseInfo['is_expired'] => ['bg' => 'red', 'text' => 'red', 'border' => 'red'],
                    $licenseInfo['is_critical'] => ['bg' => 'red', 'text' => 'red', 'border' => 'red'],
                    $licenseInfo['is_expiring_soon'] => ['bg' => 'yellow', 'text' => 'yellow', 'border' => 'yellow'],
                    default => ['bg' => 'green', 'text' => 'green', 'border' => 'green'],
                };
            @endphp
            <div class="dash-card dash-card--accent-{{ $licenseColor['border'] }} p-6" data-widget-id="license">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="dash-badge dash-badge--{{ $licenseColor['bg'] }} dash-badge--sm"><i class="fas fa-key"></i></span>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Лицензия</h2>
                    </div>
                    <div class="widget-handle cursor-move text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400" title="Перетащить">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3 dark:bg-gray-800">
                        <span class="dash-badge dash-badge--{{ $licenseColor['bg'] }} dash-badge--sm"><i class="fas fa-crown"></i></span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                {{ $licenseInfo['plan_info']['name'] ?? ucfirst($licenseInfo['subscription']->plan) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Тарифный план</p>
                        </div>
                    </div>

                    <div class="dash-license-panel dash-license-panel--{{ $licenseColor['bg'] }} rounded-xl p-4">
                        <div class="mb-1 flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Осталось времени</p>
                            @if($licenseInfo['is_expired'])
                                <span class="dash-tag dash-tag--red">Истекла</span>
                            @elseif($licenseInfo['is_critical'])
                                <span class="dash-tag dash-tag--red animate-pulse">Срочно!</span>
                            @elseif($licenseInfo['is_expiring_soon'])
                                <span class="dash-tag dash-tag--yellow">Скоро истекает</span>
                            @endif
                        </div>
                        <p class="text-2xl font-extrabold text-{{ $licenseColor['text'] }}-600 dark:text-{{ $licenseColor['text'] }}-400">
                            {{ $licenseInfo['formatted_days_left'] }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Истекает: {{ $licenseInfo['formatted_expires_at'] }}</p>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Лицензионный ключ</p>
                        <p class="break-all font-mono text-xs text-gray-900 dark:text-white">{{ $licenseInfo['subscription']->license_key }}</p>
                    </div>

                    @if($licenseInfo['is_expiring_soon'] || $licenseInfo['is_expired'])
                        <a href="{{ route('admin.subscriptions.index') }}" class="dash-pill dash-pill--primary flex w-full items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold">
                            <i class="fas fa-arrows-rotate"></i> Продлить лицензию
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Статус системы --}}
        @php
            $systemLabels = [
                'backup' => 'Резервные копии',
                'updates' => 'Обновления',
                'cache' => 'Кэш',
                'queue' => 'Очередь задач',
            ];
        @endphp
        <div class="dash-card p-6" data-widget-id="system">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="dash-badge dash-badge--gray dash-badge--sm"><i class="fas fa-gear"></i></span>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Статус системы</h2>
                </div>
                <div class="widget-handle cursor-move text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400" title="Перетащить">
                    <i class="fas fa-grip-vertical"></i>
                </div>
            </div>
            <div class="space-y-2">
                @foreach($systemStatus as $key => $status)
                    @php
                        $statusColor = match($status['status']) {
                            'success' => 'green',
                            'warning' => 'yellow',
                            'info' => 'blue',
                            default => 'gray',
                        };
                    @endphp
                    <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3 dark:bg-gray-800">
                        <span class="dash-badge dash-badge--{{ $statusColor }} dash-badge--sm"><i class="fas fa-{{ $status['icon'] }}"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemLabels[$key] ?? ucfirst($key) }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $status['message'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /*
     * Дашборд — светлое/тёмное «стекло» в духе мастера установки (см.
     * layouts/frontend-install.blade.php): полупрозрачные карточки с блюром,
     * мягкая цветная аура на фоне шапки, градиентные бейджи-иконки,
     * лёгкий подъём карточек при наведении. В отличие от установки — с
     * полноценной поддержкой тёмной темы (.dark), т.к. админка ей уже
     * пользуется, и без принудительно острых углов (это только для мастера).
     */
    .dash { animation: dashFadeIn .5s cubic-bezier(.16,1,.3,1); }
    @keyframes dashFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Стеклянные карточки ─────────────────────────────────────────── */
    .dash-hero, .dash-card, .dash-stat {
        position: relative;
        background: rgba(255,255,255,.72);
        backdrop-filter: blur(20px) saturate(160%);
        -webkit-backdrop-filter: blur(20px) saturate(160%);
        border: 1px solid rgba(255,255,255,.6);
        box-shadow: 0 18px 40px -22px rgba(15,23,42,.28), inset 0 1px 0 rgba(255,255,255,.7);
    }
    .dark .dash-hero, .dark .dash-card, .dark .dash-stat {
        background: rgba(23,28,42,.6);
        border-color: rgba(255,255,255,.08);
        box-shadow: 0 18px 40px -22px rgba(0,0,0,.6), inset 0 1px 0 rgba(255,255,255,.04);
    }
    .dash-card, .dash-stat { border-radius: 1.25rem; }
    .dash-stat { display: block; transition: transform .25s cubic-bezier(.16,1,.3,1), box-shadow .25s ease; }
    .dash-stat:hover { transform: translateY(-4px); box-shadow: 0 26px 48px -22px rgba(15,23,42,.32); }
    .dark .dash-stat:hover { box-shadow: 0 26px 48px -22px rgba(0,0,0,.7); }

    /* Тонкая цветная полоска сверху у карточек статистики */
    .dash-stat::before {
        content: ""; position: absolute; top: 0; left: 1.25rem; right: 1.25rem; height: 3px;
        border-radius: 0 0 3px 3px; opacity: .9;
    }
    .dash-stat--blue::before   { background: linear-gradient(90deg, transparent, #3b82f6, transparent); }
    .dash-stat--green::before  { background: linear-gradient(90deg, transparent, #10b981, transparent); }
    .dash-stat--purple::before { background: linear-gradient(90deg, transparent, #8b5cf6, transparent); }
    .dash-stat--orange::before { background: linear-gradient(90deg, transparent, #f97316, transparent); }
    .dash-stat { padding: 1.5rem; }

    .dash-card--accent-red::before,
    .dash-card--accent-yellow::before,
    .dash-card--accent-green::before {
        content: ""; position: absolute; top: 0; left: 1.5rem; right: 1.5rem; height: 3px; border-radius: 0 0 3px 3px;
    }
    .dash-card--accent-red::before    { background: linear-gradient(90deg, transparent, #ef4444, transparent); }
    .dash-card--accent-yellow::before { background: linear-gradient(90deg, transparent, #eab308, transparent); }
    .dash-card--accent-green::before  { background: linear-gradient(90deg, transparent, #22c55e, transparent); }

    /* ── Аура на фоне шапки (как install-backdrop, но внутри одной карточки) ── */
    .dash-hero { overflow: hidden; isolation: isolate; }
    .dash-aurora {
        position: absolute; z-index: 0; border-radius: 50%; filter: blur(70px); pointer-events: none; will-change: transform;
    }
    .dash-aurora--a {
        width: 26rem; height: 26rem; top: -11rem; left: -8rem;
        background: radial-gradient(circle at 35% 35%, rgba(99,102,241,.5), transparent 70%);
        animation: dashAuroraA 22s ease-in-out infinite alternate;
    }
    .dash-aurora--b {
        width: 22rem; height: 22rem; bottom: -12rem; right: -6rem;
        background: radial-gradient(circle at 60% 40%, rgba(236,72,153,.38), transparent 70%);
        animation: dashAuroraB 27s ease-in-out infinite alternate;
    }
    @keyframes dashAuroraA { from { transform: translate3d(0,0,0) scale(1); } to { transform: translate3d(2.5rem,2rem,0) scale(1.12); } }
    @keyframes dashAuroraB { from { transform: translate3d(0,0,0) scale(1.05); } to { transform: translate3d(-2.5rem,-1.5rem,0) scale(1); } }
    .dark .dash-aurora--a { background: radial-gradient(circle at 35% 35%, rgba(99,102,241,.28), transparent 70%); }
    .dark .dash-aurora--b { background: radial-gradient(circle at 60% 40%, rgba(236,72,153,.2), transparent 70%); }

    /* ── Градиентные бейджи-иконки ────────────────────────────────────── */
    .dash-badge {
        display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: 1rem; color: #fff; flex-shrink: 0;
        box-shadow: 0 10px 22px -10px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.35);
    }
    .dash-badge--sm { width: 2.25rem; height: 2.25rem; border-radius: .75rem; font-size: .85rem; }
    .dash-badge--blue   { background: linear-gradient(140deg,#60a5fa,#2563eb); }
    .dash-badge--green  { background: linear-gradient(140deg,#34d399,#059669); }
    .dash-badge--purple { background: linear-gradient(140deg,#a78bfa,#7c3aed); }
    .dash-badge--orange { background: linear-gradient(140deg,#fb923c,#ea580c); }
    .dash-badge--indigo { background: linear-gradient(140deg,#818cf8,#4f46e5); }
    .dash-badge--yellow { background: linear-gradient(140deg,#fbbf24,#d97706); }
    .dash-badge--red    { background: linear-gradient(140deg,#f87171,#dc2626); }
    .dash-badge--gray   { background: linear-gradient(140deg,#9ca3af,#4b5563); }

    /* ── Мини-чипы с приростом ────────────────────────────────────────── */
    .dash-chip { padding: .2rem .6rem; border-radius: 999px; font-weight: 600; }
    .dash-chip--blue   { background: color-mix(in srgb, #3b82f6 15%, transparent); color: #2563eb; }
    .dash-chip--green  { background: color-mix(in srgb, #10b981 15%, transparent); color: #059669; }
    .dash-chip--purple { background: color-mix(in srgb, #8b5cf6 15%, transparent); color: #7c3aed; }
    .dash-chip--orange { background: color-mix(in srgb, #f97316 15%, transparent); color: #ea580c; }
    .dark .dash-chip--blue   { color: #93c5fd; }
    .dark .dash-chip--green  { color: #6ee7b7; }
    .dark .dash-chip--purple { color: #c4b5fd; }
    .dark .dash-chip--orange { color: #fdba74; }

    .dash-tag { padding: .15rem .5rem; border-radius: 999px; font-size: .65rem; font-weight: 700; color: #fff; }
    .dash-tag--red { background: #ef4444; }
    .dash-tag--yellow { background: #eab308; }

    .dash-license-panel { border: 1px solid transparent; }
    .dash-license-panel--red    { background: color-mix(in srgb, #ef4444 8%, transparent); border-color: color-mix(in srgb, #ef4444 25%, transparent); }
    .dash-license-panel--yellow { background: color-mix(in srgb, #eab308 8%, transparent); border-color: color-mix(in srgb, #eab308 25%, transparent); }
    .dash-license-panel--green  { background: color-mix(in srgb, #22c55e 8%, transparent); border-color: color-mix(in srgb, #22c55e 25%, transparent); }

    /* ── Кнопки быстрого создания ─────────────────────────────────────── */
    .dash-pill {
        border-radius: .85rem; transition: transform .15s cubic-bezier(.16,1,.3,1), box-shadow .2s ease, background-color .2s ease;
        border: 1px solid rgba(255,255,255,.5);
    }
    .dash-pill:hover { transform: translateY(-2px); }
    .dash-pill:active { transform: translateY(0); }
    .dash-pill--primary { background: #111827; color: #fff; box-shadow: 0 14px 26px -12px rgba(17,24,39,.55); border-color: transparent; }
    .dash-pill--primary:hover { box-shadow: 0 18px 34px -12px rgba(79,70,229,.55); }
    .dash-pill--blue, .dash-pill--green, .dash-pill--purple, .dash-pill--orange, .dash-pill--pink, .dash-pill--indigo {
        background: rgba(255,255,255,.6); color: #1f2937;
    }
    .dark .dash-pill--blue, .dark .dash-pill--green, .dark .dash-pill--purple,
    .dark .dash-pill--orange, .dark .dash-pill--pink, .dark .dash-pill--indigo {
        background: rgba(255,255,255,.06); color: #e5e7eb; border-color: rgba(255,255,255,.1);
    }
    .dash-pill--blue i   { color: #2563eb; } .dash-pill--green i  { color: #059669; }
    .dash-pill--purple i { color: #7c3aed; } .dash-pill--orange i { color: #ea580c; }
    .dash-pill--pink i   { color: #db2777; } .dash-pill--indigo i { color: #4f46e5; }

    /* ── Строка активности ────────────────────────────────────────────── */
    .dash-activity-row { transition: background-color .15s ease; }
    .dash-activity-row:hover { background-color: rgba(0,0,0,.035); }
    .dark .dash-activity-row:hover { background-color: rgba(255,255,255,.05); }

    .hint-ico { color: #6366f1; }
    .dark .hint-ico { color: #a5b4fc; }

    @media (prefers-reduced-motion: reduce) {
        .dash, .dash-aurora { animation: none !important; }
    }
</style>
@endpush

@push('scripts')
<script src="{{ local_js('chart.min.js') }}"></script>
<script src="{{ local_js('sortable.min.js') }}"></script>
<script>
    window.dashboardCharts = @json($stats['charts'] ?? []);
</script>
{{-- Обычный vanilla-скрипт: зависит от глобальных Chart и Sortable выше,
     поэтому лежит рядом с ними в public/assets/js, а не в сборке Vite. --}}
<script src="{{ local_js('admin-dashboard.js') }}"></script>

<script>
    // Приветствие по времени суток + живые часы в шапке. Определяется здесь,
    // синхронным inline-скриптом — Alpine подключён через defer в лейауте и
    // отсканирует x-data только после парсинга документа, так что к этому
    // моменту функция уже точно объявлена.
    function dashGreeting() {
        return {
            greeting: '', icon: 'fa-sun', dateLabel: '', clock: '', timer: null,
            init() {
                this.tick();
                this.timer = setInterval(() => this.tick(), 1000);
            },
            tick() {
                const now = new Date();
                const h = now.getHours();
                if (h < 5)       { this.greeting = 'Доброй ночи';  this.icon = 'fa-moon'; }
                else if (h < 12) { this.greeting = 'Доброе утро';  this.icon = 'fa-cloud-sun'; }
                else if (h < 18) { this.greeting = 'Добрый день';  this.icon = 'fa-sun'; }
                else             { this.greeting = 'Добрый вечер'; this.icon = 'fa-moon'; }
                try {
                    this.dateLabel = now.toLocaleDateString('ru-RU', { weekday: 'long', day: 'numeric', month: 'long' });
                    this.clock = now.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                } catch (e) {
                    this.dateLabel = now.toDateString();
                    this.clock = now.toTimeString().slice(0, 8);
                }
            }
        };
    }

    // Плавный «разгон» чисел в карточках статистики при загрузке страницы.
    document.addEventListener('DOMContentLoaded', function () {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('.dash-counter').forEach(function (el) {
            const target = parseInt(el.dataset.target || '0', 10) || 0;
            if (reduceMotion || !target) {
                el.textContent = target.toLocaleString('ru-RU');
                return;
            }
            const duration = 900;
            const start = performance.now();
            function step(now) {
                const p = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(target * eased).toLocaleString('ru-RU');
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    });
</script>
@endpush
@endsection
