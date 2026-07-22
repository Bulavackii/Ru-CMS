// Dashboard с графиками
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация графиков если есть данные
    if (typeof window.dashboardCharts !== 'undefined') {
        initCharts();
    }
    
    // Инициализация drag & drop для виджетов
    initWidgetDragDrop();
});

function initCharts() {
    const canvas = document.getElementById('activityChart');
    if (!canvas) return;

    const Chart = window.Chart;
    if (!Chart) {
        console.warn('Chart.js не загружен');
        return;
    }

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(15,23,42,.06)';

    // Градиентная заливка «под линией» — та же палитра, что и у карточек
    // статистики на дашборде (синий/зелёный/оранжевый), только мягче.
    const ctx = canvas.getContext('2d');
    function fade(hex, top, bottom) {
        const g = ctx.createLinearGradient(0, 0, 0, canvas.clientHeight || 260);
        g.addColorStop(0, top);
        g.addColorStop(1, bottom);
        return g;
    }

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: window.dashboardCharts.labels,
            datasets: [
                {
                    label: 'Новости',
                    data: window.dashboardCharts.news,
                    borderColor: '#3b82f6',
                    backgroundColor: fade('#3b82f6', 'rgba(59,130,246,.28)', 'rgba(59,130,246,0)'),
                    pointBackgroundColor: '#3b82f6',
                    tension: 0.4,
                    fill: true,
                },
                {
                    label: 'Пользователи',
                    data: window.dashboardCharts.users,
                    borderColor: '#10b981',
                    backgroundColor: fade('#10b981', 'rgba(16,185,129,.24)', 'rgba(16,185,129,0)'),
                    pointBackgroundColor: '#10b981',
                    tension: 0.4,
                    fill: true,
                },
                {
                    label: 'Заказы',
                    data: window.dashboardCharts.orders,
                    borderColor: '#f97316',
                    backgroundColor: fade('#f97316', 'rgba(249,115,22,.2)', 'rgba(249,115,22,0)'),
                    pointBackgroundColor: '#f97316',
                    tension: 0.4,
                    fill: true,
                }
            ].map(function (ds) {
                return Object.assign({
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointBorderWidth: 2,
                    pointBorderColor: isDark ? '#111827' : '#ffffff',
                    pointHoverBackgroundColor: ds.pointBackgroundColor,
                    borderCapStyle: 'round',
                    borderJoinStyle: 'round',
                }, ds);
            })
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: { color: textColor, usePointStyle: true, boxWidth: 8, boxHeight: 8, padding: 16 }
                },
                tooltip: {
                    backgroundColor: isDark ? 'rgba(17,24,39,.92)' : 'rgba(17,24,39,.92)',
                    titleColor: '#f9fafb',
                    bodyColor: '#e5e7eb',
                    borderColor: 'rgba(255,255,255,.1)',
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 10,
                    displayColors: true,
                    boxPadding: 4,
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: textColor },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor, precision: 0 },
                }
            }
        }
    });
}

function initWidgetDragDrop() {
    const Sortable = window.Sortable;
    if (!Sortable) return;

    const widgetContainer = document.getElementById('dashboard-widgets');
    if (widgetContainer) {
        new Sortable(widgetContainer, {
            animation: 150,
            handle: '.widget-handle',
            onEnd: function(evt) {
                // Сохранение порядка виджетов
                saveWidgetOrder();
            }
        });
    }
}

function saveWidgetOrder() {
    const widgets = document.querySelectorAll('[data-widget-id]');
    const order = Array.from(widgets).map(w => w.dataset.widgetId);
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('CSRF token not found');
        return;
    }
    
    fetch('/admin/dashboard/save-widget-order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ order })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Можно показать уведомление об успехе
            console.log('Порядок виджетов сохранен');
        }
    })
    .catch(error => {
        console.error('Ошибка сохранения порядка виджетов:', error);
    });
}

