@extends('layouts.admin')

@section('title', 'Панель управления')

@section('content')
<div class="space-y-6">
    {{-- Заголовок --}}
    <x-admin.page-header 
        title="📊 Панель управления"
        :subtitle="'Добро пожаловать, ' . auth()->user()->name . '!'"
        :actions="collect($quickActions)->map(fn($action) => [
            'url' => $action['url'],
            'label' => $action['title'],
            'icon' => $action['icon'],
            'class' => match($action['color']) {
                'blue' => 'bg-blue-600 hover:bg-blue-700 text-white',
                'green' => 'bg-green-600 hover:bg-green-700 text-white',
                'purple' => 'bg-purple-600 hover:bg-purple-700 text-white',
                'orange' => 'bg-orange-600 hover:bg-orange-700 text-white',
                'pink' => 'bg-pink-600 hover:bg-pink-700 text-white',
                'indigo' => 'bg-indigo-600 hover:bg-indigo-700 text-white',
                default => 'bg-blue-600 hover:bg-blue-700 text-white',
            }
        ])->toArray()" />

    {{-- Статистика контента --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Новости --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Новости</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['content']['news']['total'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Опубликовано: {{ $stats['content']['news']['published'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-newspaper text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">
                    За неделю: <span class="font-semibold text-gray-900 dark:text-white">+{{ $stats['content']['news']['this_week'] }}</span>
                </p>
            </div>
        </div>

        {{-- Страницы --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Страницы</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['content']['pages']['total'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Опубликовано: {{ $stats['content']['pages']['published'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Пользователи --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Пользователи</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['users']['total'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Админов: {{ $stats['users']['admins'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">
                    За неделю: <span class="font-semibold text-gray-900 dark:text-white">+{{ $stats['users']['this_week'] }}</span>
                </p>
            </div>
        </div>

        {{-- Заказы --}}
        @if(isset($stats['orders']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Заказы</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['orders']['total'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Ожидают: {{ $stats['orders']['pending'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-orange-600 dark:text-orange-400 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">
                    Доход за месяц: <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($stats['orders']['revenue'], 0, ',', ' ') }} ₽</span>
                </p>
            </div>
        </div>
        @endif
    </div>

    {{-- График активности --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📈 Активность за последние 7 дней</h2>
        <div class="h-64">
            <canvas id="activityChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" id="dashboard-widgets">
        {{-- Последняя активность --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow p-6" data-widget-id="activity">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">📋 Последняя активность</h2>
                <div class="widget-handle cursor-move text-gray-400 hover:text-gray-600">
                    <i class="fas fa-grip-vertical"></i>
                </div>
            </div>
            <div class="space-y-3">
                @forelse($recentActivity as $activity)
                    <a href="{{ $activity['url'] }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <i class="fas fa-{{ $activity['icon'] }} text-gray-600 dark:text-gray-400"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['title'] }}</p>
                            <p class="text-xs text-gray-500">{{ $activity['user'] }} · {{ $activity['time'] }}</p>
                        </div>
                    </a>
                @empty
                    <p class="text-gray-500 text-center py-8">Активность отсутствует</p>
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
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border-2 border-{{ $licenseColor['border'] }}-300 dark:border-{{ $licenseColor['border'] }}-700 p-6" data-widget-id="license">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-key text-{{ $licenseColor['text'] }}-600 dark:text-{{ $licenseColor['text'] }}-400"></i>
                        Лицензия
                    </h2>
                    <div class="widget-handle cursor-move text-gray-400 hover:text-gray-600">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                </div>
                <div class="space-y-4">
                    {{-- Тариф --}}
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-{{ $licenseColor['bg'] }}-100 dark:bg-{{ $licenseColor['bg'] }}-900 rounded-lg flex items-center justify-center">
                                <i class="fas fa-crown text-{{ $licenseColor['text'] }}-600 dark:text-{{ $licenseColor['text'] }}-400"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $licenseInfo['plan_info']['name'] ?? ucfirst($licenseInfo['subscription']->plan) }}
                                </p>
                                <p class="text-xs text-gray-500">Тарифный план</p>
                            </div>
                        </div>
                    </div>

                    {{-- Оставшееся время --}}
                    <div class="p-4 rounded-lg bg-{{ $licenseColor['bg'] }}-50 dark:bg-{{ $licenseColor['bg'] }}-900/20 border border-{{ $licenseColor['border'] }}-200 dark:border-{{ $licenseColor['border'] }}-700">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Оставшееся время</p>
                            @if($licenseInfo['is_expired'])
                                <span class="px-2 py-1 text-xs font-bold bg-red-500 text-white rounded">Истекла</span>
                            @elseif($licenseInfo['is_critical'])
                                <span class="px-2 py-1 text-xs font-bold bg-red-500 text-white rounded animate-pulse">Срочно!</span>
                            @elseif($licenseInfo['is_expiring_soon'])
                                <span class="px-2 py-1 text-xs font-bold bg-yellow-500 text-white rounded">Скоро истекает</span>
                            @endif
                        </div>
                        <p class="text-2xl font-bold text-{{ $licenseColor['text'] }}-600 dark:text-{{ $licenseColor['text'] }}-400">
                            {{ $licenseInfo['formatted_days_left'] }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Истекает: {{ $licenseInfo['formatted_expires_at'] }}
                        </p>
                    </div>

                    {{-- Лицензионный ключ --}}
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
                        <p class="text-xs text-gray-500 mb-1">Лицензионный ключ</p>
                        <p class="text-sm font-mono text-gray-900 dark:text-white break-all">
                            {{ $licenseInfo['subscription']->license_key }}
                        </p>
                    </div>

                    @if($licenseInfo['is_expiring_soon'] || $licenseInfo['is_expired'])
                        <a href="{{ route('admin.subscriptions.index') }}" 
                           class="block w-full text-center px-4 py-2 bg-{{ $licenseColor['bg'] }}-600 hover:bg-{{ $licenseColor['bg'] }}-700 text-white rounded-lg transition font-medium">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Продлить лицензию
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Статус системы --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" data-widget-id="system">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">⚙️ Статус системы</h2>
                <div class="widget-handle cursor-move text-gray-400 hover:text-gray-600">
                    <i class="fas fa-grip-vertical"></i>
                </div>
            </div>
            <div class="space-y-3">
                @foreach($systemStatus as $key => $status)
                    @php
                        $statusColor = match($status['status']) {
                            'success' => ['bg' => 'green', 'text' => 'green'],
                            'warning' => ['bg' => 'yellow', 'text' => 'yellow'],
                            'info' => ['bg' => 'blue', 'text' => 'blue'],
                            default => ['bg' => 'gray', 'text' => 'gray'],
                        };
                    @endphp
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
                        <div class="w-10 h-10 bg-{{ $statusColor['bg'] }}-100 dark:bg-{{ $statusColor['bg'] }}-900 rounded-lg flex items-center justify-center">
                            <i class="fas fa-{{ $status['icon'] }} text-{{ $statusColor['text'] }}-600 dark:text-{{ $statusColor['text'] }}-400"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ $key }}</p>
                            <p class="text-xs text-gray-500">{{ $status['message'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ local_js('chart.min.js') }}"></script>
<script src="{{ local_js('sortable.min.js') }}"></script>
<script>
    window.dashboardCharts = @json($stats['charts'] ?? []);
</script>
<script src="{{ asset('js/admin/dashboard.js') }}"></script>
@endpush
@endsection

