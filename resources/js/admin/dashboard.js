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
    const ctx = document.getElementById('activityChart');
    if (!ctx) return;

    const Chart = window.Chart;
    if (!Chart) {
        console.warn('Chart.js не загружен');
        return;
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: window.dashboardCharts.labels,
            datasets: [
                {
                    label: 'Новости',
                    data: window.dashboardCharts.news,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Пользователи',
                    data: window.dashboardCharts.users,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Заказы',
                    data: window.dashboardCharts.orders,
                    borderColor: 'rgb(249, 115, 22)',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Активность за последние 7 дней'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
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

