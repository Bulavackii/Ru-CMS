@extends('layouts.admin')

@section('title', 'Аналитика')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📊 Аналитика</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Статистика посещаемости и популярного контента</p>
        </div>
        <div class="flex gap-2">
            <select id="period-select" class="border rounded px-3 py-2">
                <option value="day" {{ $period === 'day' ? 'selected' : '' }}>День</option>
                <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Неделя</option>
                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Месяц</option>
                <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Год</option>
            </select>
            <a href="{{ route('admin.analytics.settings') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                ⚙️ Настройки
            </a>
        </div>
    </div>

    {{-- Яндекс.Метрика данные --}}
    @if(isset($yandexData) && !isset($yandexData['error']))
    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
        <h3 class="font-semibold mb-2">📈 Данные из Яндекс.Метрики</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <div class="text-2xl font-bold">{{ number_format($yandexData['visits'] ?? 0) }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Визиты</div>
            </div>
            <div>
                <div class="text-2xl font-bold">{{ number_format($yandexData['pageviews'] ?? 0) }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Просмотры</div>
            </div>
            <div>
                <div class="text-2xl font-bold">{{ number_format($yandexData['users'] ?? 0) }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Пользователи</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Основная статистика --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-3xl font-bold text-blue-600">{{ number_format($stats['unique_visitors'] ?? 0) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Уникальных посетителей</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-3xl font-bold text-green-600">{{ number_format($stats['total_page_views'] ?? 0) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Просмотров страниц</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-3xl font-bold text-purple-600">{{ number_format($stats['content_views'] ?? 0) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Просмотров контента</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-3xl font-bold text-orange-600">{{ gmdate('H:i:s', $stats['avg_session_duration'] ?? 0) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Средняя сессия</div>
        </div>
    </div>

    {{-- Графики --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- График просмотров (линейный) --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">📈 График просмотров</h2>
            <div style="height: 300px;">
                <canvas id="viewsChart"></canvas>
            </div>
        </div>

        {{-- График по типам контента (круговая диаграмма) --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">📊 Просмотры по типам</h2>
            <div style="height: 300px;">
                <canvas id="contentTypesChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Популярные новости --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">📰 Популярные новости</h2>
            <div class="space-y-3">
                @forelse($popularNews as $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <div class="flex-1">
                            <div class="font-medium">{{ $item['title'] }}</div>
                            <div class="text-sm text-gray-500">{{ $item['views'] }} просмотров</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">Нет данных</p>
                @endforelse
            </div>
        </div>

        {{-- Популярные страницы --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">📄 Популярные страницы</h2>
            <div class="space-y-3">
                @forelse($popularPages as $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <div class="flex-1">
                            <div class="font-medium">{{ $item['title'] }}</div>
                            <div class="text-sm text-gray-500">{{ $item['views'] }} просмотров</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">Нет данных</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ local_js('chart.min.js') }}"></script>
<script>
    // График просмотров (линейный)
    const ctx = document.getElementById('viewsChart');
    const viewsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($viewsChart['labels'] ?? []),
            datasets: [{
                label: 'Просмотры',
                data: @json($viewsChart['data'] ?? []),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // График по типам контента (круговая диаграмма)
    const ctxTypes = document.getElementById('contentTypesChart');
    const contentTypesData = @json($stats['views_by_type'] ?? []);
    const contentTypesChart = new Chart(ctxTypes, {
        type: 'doughnut',
        data: {
            labels: Object.keys(contentTypesData),
            datasets: [{
                data: Object.values(contentTypesData),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Изменение периода
    document.getElementById('period-select').addEventListener('change', function() {
        const period = this.value;
        window.location.href = '{{ route("admin.analytics.index") }}?period=' + period;
    });
</script>
@endpush
@endsection

