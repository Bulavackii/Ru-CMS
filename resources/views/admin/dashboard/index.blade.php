@extends('layouts.admin')

@section('title', __('admin.dashboard.title'))

@section('content')
<div class="dash space-y-6" x-data="dashGreeting()">

    {{-- ═══════════════════════ Приветственный блок ═══════════════════════ --}}
    <div class="dash-hero rounded-3xl px-5 py-4 sm:px-6 sm:py-4">
        <span class="dash-aurora dash-aurora--a" aria-hidden="true"></span>
        <span class="dash-aurora dash-aurora--b" aria-hidden="true"></span>

        <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white break-words">
                    <span x-text="greeting">{{ __('admin.dashboard.welcome') }}</span>,
                    {{ auth()->user()->name }} <span aria-hidden="true">👋</span>
                </h1>
                {{-- Дата, часы и заметка об обновлении статистики — одной
                     строкой. Прежде под ними шла отдельная полоса с рамкой,
                     и приветствие занимало 215px при трёх строках текста. --}}
                <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <span class="capitalize" x-text="dateLabel"></span>
                    <span class="inline-flex items-center gap-1.5">
                        <i class="fas fa-clock hint-ico"></i><span class="font-mono" x-text="clock"></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <i class="fas fa-bolt hint-ico"></i>{{ __('admin.dashboard.stats_note') }}
                    </span>
                    @if($licenseInfo && ($licenseInfo['is_expiring_soon'] || $licenseInfo['is_expired']))
                        <a href="{{ route('admin.subscriptions.index') }}"
                           class="inline-flex items-center gap-1.5 font-semibold text-red-600 dark:text-red-400 hover:underline">
                            <i class="fas fa-triangle-exclamation"></i>
                            Лицензия {{ $licenseInfo['is_expired'] ? 'истекла' : 'скоро истекает' }}
                        </a>
                    @endif
                </p>
            </div>

            {{-- Быстрое создание: одна основная кнопка + пилюли-акценты --}}
            <div class="flex flex-wrap items-center gap-2">
                @foreach($quickActions as $i => $action)
                    <a href="{{ $action['url'] }}"
                       class="dash-pill {{ $i === 0 ? 'dash-pill--primary' : 'dash-pill--' . $action['color'] }} inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold">
                        <i class="fas fa-{{ $action['icon'] }}"></i>
                        <span class="{{ $i === 0 ? '' : 'hidden xl:inline' }}">{{ $action['title'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ═══════════════════════ Карточки статистики ═══════════════════════ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('admin.news.index') }}" class="dash-stat dash-stat--blue group">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="dash-stat__label">{{ __('admin.sections.news') }}</p>
                    <p class="dash-counter mt-0.5 text-2xl font-extrabold text-gray-900 dark:text-white" data-target="{{ $stats['content']['news']['total'] }}">0</p>
                </div>
                <span class="dash-badge dash-badge--blue"><i class="fas fa-newspaper"></i></span>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs">
                <span class="dash-chip dash-chip--blue">+{{ $stats['content']['news']['this_week'] }} {{ __('admin.dashboard.per_week') }}</span>
                <span class="text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-gray-600 dark:group-hover:text-gray-300">
                    {{ $stats['content']['news']['published'] }} {{ __('admin.dashboard.pub_short') }} <i class="fas fa-arrow-right ml-0.5"></i>
                </span>
            </div>
        </a>

        <a href="{{ route('admin.pages.index') }}" class="dash-stat dash-stat--green group">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="dash-stat__label">{{ __('admin.sections.pages') }}</p>
                    <p class="dash-counter mt-0.5 text-2xl font-extrabold text-gray-900 dark:text-white" data-target="{{ $stats['content']['pages']['total'] }}">0</p>
                </div>
                <span class="dash-badge dash-badge--green"><i class="fas fa-file-lines"></i></span>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs">
                <span class="dash-chip dash-chip--green">{{ $stats['content']['pages']['published'] }} {{ __('admin.dashboard.published_word') }}</span>
                <span class="text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-gray-600 dark:group-hover:text-gray-300">
                    {{ __('admin.dashboard.all_pages') }} <i class="fas fa-arrow-right ml-0.5"></i>
                </span>
            </div>
        </a>

        <a href="{{ route('admin.users.index') }}" class="dash-stat dash-stat--purple group">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="dash-stat__label">{{ __('admin.sections.users') }}</p>
                    <p class="dash-counter mt-0.5 text-2xl font-extrabold text-gray-900 dark:text-white" data-target="{{ $stats['users']['total'] }}">0</p>
                </div>
                <span class="dash-badge dash-badge--purple"><i class="fas fa-users"></i></span>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs">
                <span class="dash-chip dash-chip--purple">+{{ $stats['users']['this_week'] }} {{ __('admin.dashboard.per_week') }}</span>
                <span class="text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-gray-600 dark:group-hover:text-gray-300">
                    {{ $stats['users']['admins'] }} {{ __('admin.dashboard.admins_short') }} <i class="fas fa-arrow-right ml-0.5"></i>
                </span>
            </div>
        </a>

        @if(isset($stats['orders']))
            <a href="{{ route('admin.orders.index') }}" class="dash-stat dash-stat--orange group">
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <p class="dash-stat__label">{{ __('admin.sections.orders') }}</p>
                        <p class="dash-counter mt-0.5 text-2xl font-extrabold text-gray-900 dark:text-white" data-target="{{ $stats['orders']['total'] }}">0</p>
                    </div>
                    <span class="dash-badge dash-badge--orange"><i class="fas fa-cart-shopping"></i></span>
                </div>
                <div class="mt-2 flex items-center justify-between text-xs">
                    <span class="dash-chip dash-chip--orange">{{ $stats['orders']['pending'] }} {{ __('admin.dashboard.pending') }}</span>
                    <span class="text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-gray-600 dark:group-hover:text-gray-300">
                        {{ number_format($stats['orders']['revenue'], 0, ',', ' ') }} {{ __('admin.dashboard.per_month') }} <i class="fas fa-arrow-right ml-0.5"></i>
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
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('admin.dashboard.chart_title') }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.dashboard.chart_sub') }}</p>
            </div>
        </div>
        <div class="dash-chart">
            <canvas id="activityChart"></canvas>
        </div>
    </div>

    {{-- ═══════════════════════ Виджеты (перетаскиваются) ═══════════════════════ --}}
    {{-- ── Короткая сводка ────────────────────────────────────────────
         Числа ниже контроллер считал и раньше, но на странице их не было
         вовсе: черновики, объём медиатеки и вошедшие сегодня. Именно они
         отвечают на вопрос «что происходит прямо сейчас», а карточки выше —
         на вопрос «сколько всего». --}}
    @php
        $facts = array_values(array_filter([
            [
                'label' => 'Черновики',
                'value' => $stats['content']['news']['draft'] ?? 0,
                'note'  => 'новости, не видные на сайте',
                'icon'  => 'fa-file-pen',
            ],
            [
                'label' => 'Заходили сегодня',
                'value' => $stats['users']['active_today'] ?? 0,
                'note'  => 'пользователей',
                'icon'  => 'fa-user-clock',
            ],
            [
                'label' => 'Медиатека',
                'value' => $stats['content']['files']['total'] ?? 0,
                'note'  => trim((string) ($stats['content']['files']['size'] ?? '')) ?: 'файлов',
                'icon'  => 'fa-photo-film',
            ],
            isset($stats['orders']) ? [
                'label' => 'Заказы за месяц',
                'value' => $stats['orders']['this_month'] ?? 0,
                'note'  => ($stats['orders']['completed'] ?? 0) . ' завершено',
                'icon'  => 'fa-receipt',
            ] : null,
        ]));
    @endphp

    <div class="dash-facts mb-5">
        @foreach($facts as $fact)
            <div class="dash-fact">
                <span class="dash-fact__ico"><i class="fas {{ $fact['icon'] }}"></i></span>
                <div class="min-w-0">
                    <span class="dash-fact__value">{{ $fact['value'] }}</span>
                    <span class="dash-fact__label">{{ $fact['label'] }}</span>
                    <span class="dash-fact__note">{{ $fact['note'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3" id="dashboard-widgets">

        {{-- Последняя активность --}}
        <div class="dash-card lg:col-span-2 p-6" data-widget-id="activity">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="dash-badge dash-badge--gray dash-badge--sm"><i class="fas fa-list-check"></i></span>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('admin.dashboard.recent') }}</h2>
                </div>
                <div class="widget-handle cursor-move text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400" title="{{ __('admin.dashboard.drag') }}">
                    <i class="fas fa-grip-vertical"></i>
                </div>
            </div>
            {{-- Лента: слева тонкая линия времени, у каждой записи своя
                 точка в цвете типа. Раньше это был плоский список из
                 десяти одинаковых строк — понять, что новое, а что
                 недельной давности, можно было только вчитываясь. --}}
            <div class="dash-feed">
                @forelse($recentActivity as $activity)
                    <a href="{{ $activity['url'] }}" class="dash-feed__row dash-feed__row--{{ $activity['type'] ?? 'other' }}">
                        <span class="dash-feed__dot"><i class="fas fa-{{ $activity['icon'] }}"></i></span>

                        <span class="dash-feed__body">
                            <span class="dash-feed__title">{{ $activity['title'] }}</span>
                            <span class="dash-feed__meta">
                                <span class="dash-feed__who">{{ $activity['user'] }}</span>
                                <span class="dash-feed__when">{{ $activity['time'] }}</span>
                            </span>
                        </span>

                        <i class="fas fa-chevron-right dash-feed__go" aria-hidden="true"></i>
                    </a>
                @empty
                    <p class="dash-feed__empty">{{ __('admin.dashboard.recent_empty') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Лицензия --}}
        {{-- ── Требует внимания ──────────────────────────────────────────
             Дашборд показывал только сводные числа: сколько всего новостей,
             страниц и пользователей. По ним видно состояние, но не видно, что
             СДЕЛАТЬ — незамеченный черновик или пустое меню так и оставались
             незамеченными, пока не зайдёшь в раздел.

             Пункты с нулём не показываются: список «всё по нулям» ничего не
             сообщает и лишь приучает его не читать. --}}
        <div class="dash-card dash-card--fit p-6" data-widget-id="attention">
            <div class="mb-4 flex items-center gap-3">
                <span class="dash-badge dash-badge--sm" style="background:linear-gradient(135deg,#f59e0b,#f97316)">
                    <i class="fas fa-circle-exclamation"></i>
                </span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Требует внимания</h2>
            </div>

            @forelse($attention as $item)
                <a href="{{ $item['url'] }}" class="dash-att {{ $item['tone'] === 'info' ? 'is-info' : '' }}">
                    <i class="fas {{ $item['icon'] }}"></i>
                    <span class="dash-att__label">{{ $item['label'] }}</span>
                    <span class="dash-att__count">{{ $item['count'] }}</span>
                    <i class="fas fa-chevron-right dash-att__go"></i>
                </a>
            @empty
                {{-- Пустой список — это хорошая новость, и выглядеть она
                     должна так же: спокойный блок, а не пустая карточка на
                     всю высоту соседа. --}}
                <p class="dash-att__clear">
                    <i class="fas fa-circle-check"></i>
                    <span>Всё в порядке: черновиков, непрочитанных сообщений и пустых меню нет.</span>
                </p>
            @endforelse
        </div>

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
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('admin.dashboard.license') }}</h2>
                    </div>
                    <div class="widget-handle cursor-move text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400" title="{{ __('admin.dashboard.drag') }}">
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
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.dashboard.plan') }}</p>
                        </div>
                    </div>

                    <div class="dash-license-panel dash-license-panel--{{ $licenseColor['bg'] }} rounded-xl p-4">
                        <div class="mb-1 flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('admin.dashboard.time_left') }}</p>
                            @if($licenseInfo['is_expired'])
                                <span class="dash-tag dash-tag--red">{{ __('admin.dashboard.expired') }}</span>
                            @elseif($licenseInfo['is_critical'])
                                <span class="dash-tag dash-tag--red animate-pulse">{{ __('admin.dashboard.urgent') }}</span>
                            @elseif($licenseInfo['is_expiring_soon'])
                                <span class="dash-tag dash-tag--yellow">{{ __('admin.dashboard.expiring') }}</span>
                            @endif
                        </div>
                        <p class="text-2xl font-extrabold text-{{ $licenseColor['text'] }}-600 dark:text-{{ $licenseColor['text'] }}-400">
                            {{ $licenseInfo['formatted_days_left'] }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Истекает: {{ $licenseInfo['formatted_expires_at'] }}</p>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800">
                        <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.dashboard.license_key') }}</p>
                        <p class="break-all font-mono text-xs text-gray-900 dark:text-white">{{ $licenseInfo['subscription']->license_key }}</p>
                    </div>

                    @if($licenseInfo['is_expiring_soon'] || $licenseInfo['is_expired'])
                        <a href="{{ route('admin.subscriptions.index') }}" class="dash-pill dash-pill--primary flex w-full items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold">
                            <i class="fas fa-arrows-rotate"></i> {{ __('admin.dashboard.renew') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Статус системы --}}
        @php
            $systemLabels = [
                'backup' => __('admin.dashboard.st_backup'),
                'updates' => __('admin.dashboard.st_updates'),
                'cache' => __('admin.dashboard.st_cache'),
                'queue' => __('admin.dashboard.st_queue'),
            ];
        @endphp
        {{-- ── Безопасность ──────────────────────────────────────────────
             Только ПРОВЕРЯЕМЫЕ факты — конкретные настройки, которые видно из
             приложения и которые владелец может изменить сам.

             Чего здесь нет и почему: стойкость пароля администратора проверить
             нельзя (в базе только хеш), а «сайт не взломан» — утверждение,
             которое приложение о себе знать не может. Обещать такое на
             дашборде значило бы обманывать. --}}
        <div class="dash-card dash-card--fit p-5" data-widget-id="security">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="dash-badge dash-badge--sm"
                          style="background:linear-gradient(135deg,{{ $security['bad'] ? '#ef4444,#f97316' : '#16a34a,#22c55e' }})">
                        <i class="fas fa-shield-halved"></i>
                    </span>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Безопасность</h2>
                </div>
                @if($security['bad'])
                    <span class="dash-sec__flag">{{ $security['bad'] }} на проверку</span>
                @endif
            </div>

            <div class="dash-sec">
                @foreach($security['items'] as $item)
                    <div class="dash-sec__row {{ $item['ok'] ? 'is-ok' : 'is-bad' }}">
                        <i class="fas {{ $item['ok'] ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                        <div class="min-w-0">
                            <span class="dash-sec__label">{{ $item['label'] }}</span>
                            <span class="dash-sec__note">{{ $item['note'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── Обновление ────────────────────────────────────────────────
             Проверка идёт на СВОЙ сервер, адрес которого задаёт владелец. По
             умолчанию он пуст, и запроса наружу нет вовсе. --}}
        <div class="dash-card dash-card--fit p-5" data-widget-id="updates">
            <div class="mb-4 flex items-center gap-3">
                <span class="dash-badge dash-badge--sm"
                      style="background:linear-gradient(135deg,{{ $updates['available'] ? '#f59e0b,#f97316' : '#6366f1,#8b5cf6' }})">
                    <i class="fas fa-arrow-up-from-bracket"></i>
                </span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Обновление</h2>
            </div>

            <div class="dash-upd">
                <div class="dash-upd__now">
                    <span class="dash-upd__cap">Установлено</span>
                    <span class="dash-upd__ver">{{ $updates['current'] }}</span>
                </div>

                @if($updates['available'] && $updates['latest'])
                    <div class="dash-upd__now is-new">
                        <span class="dash-upd__cap">Доступно</span>
                        <span class="dash-upd__ver">{{ $updates['latest'] }}</span>
                    </div>
                @endif
            </div>

            <p class="dash-upd__note">{{ $updates['note'] }}</p>
        </div>

        <div class="dash-card dash-card--fit p-5" data-widget-id="system">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="dash-badge dash-badge--gray dash-badge--sm"><i class="fas fa-gear"></i></span>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('admin.dashboard.system_status') }}</h2>
                </div>
                <div class="widget-handle cursor-move text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400" title="{{ __('admin.dashboard.drag') }}">
                    <i class="fas fa-grip-vertical"></i>
                </div>
            </div>
            {{-- Строки-состояния: подпись слева, значение справа. Прежде
                 каждая была плиткой с крупным значком и занимала 56px —
                 четыре состояния растягивали карточку вдвое. --}}
            <div class="dash-sys">
                @foreach($systemStatus as $key => $status)
                    @php
                        $tone = match($status['status']) {
                            'success' => 'is-ok',
                            'warning' => 'is-warn',
                            'info' => 'is-info',
                            default => '',
                        };
                    @endphp
                    <div class="dash-sys__row {{ $tone }}">
                        <i class="fas fa-{{ $status['icon'] }}"></i>
                        <span class="dash-sys__label">{{ $systemLabels[$key] ?? ucfirst($key) }}</span>
                        <span class="dash-sys__value">{{ $status['message'] }}</span>
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
    /* Поле графика: фиксированная высота нужна Chart.js — при
       maintainAspectRatio:false он растягивается по контейнеру, а без
       высоты контейнер схлопывается в ноль. */
    .dash-chart{ position:relative; height:17rem }
    /* Пустая неделя: вместо трёх прямых по нижнему краю — одна фраза. */
    .dash-chart__empty{ position:absolute; inset:0; display:flex; align-items:center;
        justify-content:center; margin:0; padding:0 1rem; text-align:center;
        font-size:.85rem; color:#6b7280 }
    .dark .dash-chart__empty{ color:#9ca3af }
    @media (max-width:640px){ .dash-chart{ height:13rem } }
    .dash-stat { display: block; transition: transform .25s cubic-bezier(.16,1,.3,1), box-shadow .25s ease; }
    /* Подпись карточки — моноширинным капсом, как подписи в разделах
       панели: так она не спорит с числом и занимает одну строку. */
    .dash-stat__label{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.62rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
        color:#6b7280 }
    .dark .dash-stat__label{ color:#9ca3af }
    .dash-stat .dash-badge{ width:2.25rem; height:2.25rem; font-size:.9rem }
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
    .dash-stat { padding: .9rem 1rem; }

/* ── Короткая сводка ────────────────────────────────────────────── */
.dash-facts{ display:grid; gap:.75rem; grid-template-columns:repeat(auto-fit, minmax(min(100%, 13rem), 1fr)) }
.dash-fact{ display:flex; align-items:center; gap:.6rem; padding:.5rem .7rem;
    background:#fff; border:1px solid #eef2f7 }
.dark .dash-fact{ background:#111827; border-color:#374151 }
.dash-fact__ico{ display:flex; align-items:center; justify-content:center; flex:none;
    font-size:.8rem;
    width:2.1rem; height:2.1rem; color:var(--admin-primary);
    background:color-mix(in srgb, var(--admin-primary) 12%, transparent) }
.dash-fact__value{ display:block; font-size:1.1rem; font-weight:800; line-height:1.1; color:#111827;
    font-variant-numeric:tabular-nums }
.dark .dash-fact__value{ color:#f3f4f6 }
.dash-fact__label{ display:block; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
    font-size:.6rem; font-weight:700; letter-spacing:.09em; text-transform:uppercase; color:#4b5563 }
.dark .dash-fact__label{ color:#d1d5db }
.dash-fact__note{ display:block; font-size:.68rem;
    color:color-mix(in srgb, var(--surface-ink,#111827) 60%, var(--surface,#fff));
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap }

/* ── Требует внимания ───────────────────────────────────────────── */
    /* Карточка не растягивается по высоте соседа: у «Требует внимания»
       содержимого на пару строк, а рядом лента из десяти записей. */
    .dash-card--fit{ align-self:start }

    /* ── Лента последней активности ──────────────────────────────── */
    .dash-feed{ position:relative; display:grid; gap:.1rem; padding-left:.1rem }
    /* Линия времени: одна вертикаль на всю ленту, точки записей стоят
       на ней. */
    .dash-feed::before{ content:''; position:absolute; left:1.05rem; top:.9rem; bottom:.9rem;
        width:1px; background:#e5e7eb }
    .dark .dash-feed::before{ background:#374151 }

    .dash-feed__row{ position:relative; display:flex; align-items:center; gap:.7rem;
        padding:.45rem .5rem; transition:background .15s ease }
    .dash-feed__row:hover{ background:#f9fafb }
    .dark .dash-feed__row:hover{ background:#1f2937 }

    .dash-feed__dot{ position:relative; z-index:1; display:grid; place-items:center; flex:none;
        width:1.9rem; height:1.9rem; font-size:.7rem; color:#6b7280;
        background:#f3f4f6; border:2px solid #fff }
    .dark .dash-feed__dot{ color:#9ca3af; background:#1f2937; border-color:#111827 }
    /* Цвет точки по типу записи — те же цвета, что у карточек статистики. */
    .dash-feed__row--news .dash-feed__dot{ color:#1d4ed8; background:#dbeafe }
    .dash-feed__row--page .dash-feed__dot{ color:#15803d; background:#dcfce7 }
    .dash-feed__row--user .dash-feed__dot{ color:#6d28d9; background:#ede9fe }
    .dash-feed__row--order .dash-feed__dot{ color:#c2410c; background:#ffedd5 }

    /* ⚠️ min-width:0 нужен и САМОЙ строке, а не только её телу. Лента —
       это grid, а элемент сетки по умолчанию не сжимается ниже содержимого
       (min-width:auto): длинный заголовок материала распирал строку до 389
       пикселей при экране 375, и вся страница ехала вбок. Усечение
       заголовка при этом не срабатывало — усекать было нечего, блок рос
       под текст. */
    .dash-feed__row{ min-width:0 }
    .dash-feed__body{ display:flex; flex-direction:column; gap:.1rem; min-width:0; flex:1 }
    .dash-feed__title{ font-size:.85rem; font-weight:600; color:#111827;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .dark .dash-feed__title{ color:#f3f4f6 }
    .dash-feed__meta{ display:flex; align-items:center; gap:.4rem; font-size:.68rem;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }
    .dash-feed__who{ font-weight:600 }
    .dash-feed__when{ font-family:ui-monospace,SFMono-Regular,Menlo,monospace; letter-spacing:.02em }
    .dash-feed__when::before{ content:'·'; margin-right:.4rem }

    .dash-feed__go{ font-size:.65rem; color:#d1d5db; opacity:0; transition:opacity .15s ease }
    .dash-feed__row:hover .dash-feed__go{ opacity:1 }

    .dash-feed__empty{ padding:2.5rem 0; text-align:center; font-size:.85rem;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }

    .dash-att{ display:flex; align-items:center; gap:.6rem; padding:.55rem .7rem;
        font-size:.85rem; text-decoration:none; color:#4b5563;
        border:1px solid #f0d9a8; background:#fffbeb; transition:border-color .15s }
    .dash-att + .dash-att{ margin-top:.4rem }
    .dash-att:hover{ border-color:#f59e0b }
    .dash-att > i:first-child{ color:#d97706; width:1rem; text-align:center; flex:none }
    .dash-att.is-info{ border-color:#c7d2fe; background:#eef2ff }
    .dash-att.is-info > i:first-child{ color:var(--admin-primary) }
    .dash-att__label{ flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
    .dash-att__count{ font-weight:800; color:#111827; flex:none }
    .dash-att__go{ font-size:.7rem; opacity:.45; flex:none }
    .dash-att__clear{ display:flex; align-items:center; gap:.5rem; font-size:.85rem; color:#166534;
        padding:.55rem .7rem; background:#f0fdf4; border:1px solid #bbf7d0 }

    /* ── Безопасность ───────────────────────────────────────────────── */
    .dash-sec{ display:flex; flex-direction:column; gap:.55rem }
    .dash-sec__row{ display:flex; align-items:flex-start; gap:.55rem; font-size:.82rem }
    .dash-sec__row > i{ margin-top:.15rem; width:1rem; text-align:center; flex:none }
    .dash-sec__row.is-ok > i{ color:#16a34a }
    .dash-sec__row.is-bad > i{ color:#dc2626 }
    .dash-sec__label{ display:block; font-weight:600; color:#111827 }
    .dash-sec__note{ display:block; margin-top:.1rem; font-size:.75rem; line-height:1.4;
        color:color-mix(in srgb, var(--surface-ink,#111827) 68%, var(--surface,#fff)) }
    .dash-sec__flag{ font-size:.68rem; font-weight:700; padding:.15rem .45rem;
        color:#991b1b; background:#fee2e2; border:1px solid #fecaca; flex:none }

    /* ── Обновление ─────────────────────────────────────────────────── */
    .dash-upd{ display:flex; flex-wrap:wrap; gap:.6rem }
    .dash-upd__now{ flex:1; min-width:7rem; padding:.55rem .7rem; background:#f9fafb; border:1px solid #e5e7eb }
    .dash-upd__now.is-new{ background:#fffbeb; border-color:#f0d9a8 }
    .dash-upd__cap{ display:block; font-size:.65rem; font-weight:700; letter-spacing:.06em;
        text-transform:uppercase; color:#9ca3af }
    .dash-upd__ver{ display:block; margin-top:.15rem; font-size:1.05rem; font-weight:800; color:#111827 }
    .dash-upd__note{ margin-top:.6rem; font-size:.75rem; line-height:1.45; color:#6b7280 }

    /* ── Статус системы ──────────────────────────────────────────────
       Строка на состояние: значок, подпись, значение справа. Значения
       выровнены по правому краю — так четыре строки читаются столбиком,
       а не четырьмя разными абзацами. */
    .dash-sys{ display:grid; gap:.15rem }
    .dash-sys__row{ display:flex; align-items:center; gap:.55rem; padding:.45rem .1rem;
        border-bottom:1px dashed #eef2f7 }
    .dash-sys__row:last-child{ border-bottom:0 }
    .dark .dash-sys__row{ border-bottom-color:#374151 }
    .dash-sys__row > i{ width:1.1rem; text-align:center; flex:none; font-size:.78rem; color:#9ca3af }
    .dash-sys__row.is-ok > i{ color:#16a34a }
    .dash-sys__row.is-warn > i{ color:#d97706 }
    .dash-sys__row.is-info > i{ color:#4f46e5 }
    .dash-sys__label{ font-size:.82rem; font-weight:600; color:#111827 }
    .dark .dash-sys__label{ color:#f3f4f6 }
    .dash-sys__value{ margin-left:auto; text-align:right; font-size:.75rem;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        color:color-mix(in srgb, var(--surface-ink,#111827) 62%, var(--surface,#fff)) }

    /* Заголовки карточек нижнего ряда — вровень с остальной панелью. */
    [data-widget-id="security"] h2,
    [data-widget-id="updates"] h2,
    [data-widget-id="system"] h2{ font-size:1rem }

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

    // Подписи графика — из словаря: сам скрипт лежит в public/ и русского
    // текста содержать не должен, панель переводится.
    window.dashboardChartStrings = {
        news: @js(__('admin.sections.news')),
        users: @js(__('admin.sections.users')),
        orders: @js(__('admin.sections.orders')),
        empty: @js(__('admin.dashboard.chart_empty')),
        day_total: @js(__('admin.dashboard.chart_day_total')),
    };
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
