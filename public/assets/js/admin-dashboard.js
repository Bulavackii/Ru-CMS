// Dashboard с графиками
document.addEventListener('DOMContentLoaded', function () {
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

    const data = window.dashboardCharts || {};
    const labels = data.labels || [];
    const strings = window.dashboardChartStrings || {};

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const gridColor = isDark ? 'rgba(255,255,255,.07)' : 'rgba(15,23,42,.07)';
    const surface = isDark ? '#111827' : '#ffffff';

    const series = [
        { key: 'news', color: '#3b82f6', label: strings.news || 'Новости' },
        { key: 'users', color: '#10b981', label: strings.users || 'Пользователи' },
        { key: 'orders', color: '#f97316', label: strings.orders || 'Заказы' },
    ].map(function (s) {
        s.values = (data[s.key] || []).map(function (v) { return Number(v) || 0; });
        s.total = s.values.reduce(function (a, b) { return a + b; }, 0);
        return s;
    });

    // ── Пустая неделя ────────────────────────────────────────────────
    // Раньше при нулях рисовались три прямые по нижнему краю, ось Y
    // размечалась 0…1, а сглаживание превращало единственный заказ в
    // «холм» на полнедели — график показывал то, чего не было. Если
    // событий нет вовсе, честнее сказать это словами.
    const hasData = series.some(function (s) { return s.total > 0; });

    if (!hasData) {
        const box = canvas.parentElement;
        if (box) {
            const empty = document.createElement('p');
            empty.className = 'dash-chart__empty';
            empty.textContent = strings.empty || 'За эти дни ничего не происходило.';
            box.appendChild(empty);
        }
        canvas.style.display = 'none';
        return;
    }

    // Подпись легенды несёт сумму за период: без неё приходилось наводить
    // на каждую точку, чтобы понять, чего сколько.
    const legendLabel = function (s) {
        return s.label + ' · ' + s.total;
    };

    // Градиентная заливка «под линией» — та же палитра, что и у карточек
    // статистики на дашборде (синий/зелёный/оранжевый), только мягче.
    const ctx = canvas.getContext('2d');
    const fade = function (rgba) {
        const g = ctx.createLinearGradient(0, 0, 0, canvas.clientHeight || 260);
        g.addColorStop(0, rgba);
        g.addColorStop(1, 'rgba(0,0,0,0)');
        return g;
    };

    const softColors = {
        '#3b82f6': 'rgba(59,130,246,.26)',
        '#10b981': 'rgba(16,185,129,.22)',
        '#f97316': 'rgba(249,115,22,.20)',
    };

    // Верхняя граница оси: ровное число с запасом, иначе линия упирается
    // в самый верх поля и выглядит обрезанной.
    const peak = Math.max.apply(null, series.map(function (s) {
        return Math.max.apply(null, s.values.concat([0]));
    }));
    const step = peak <= 4 ? 1 : Math.ceil(peak / 4);
    const suggestedMax = Math.max(step, Math.ceil((peak + step * 0.4) / step) * step);

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: series.map(function (s) {
                return {
                    label: legendLabel(s),
                    data: s.values,
                    borderColor: s.color,
                    backgroundColor: fade(softColors[s.color]),
                    pointBackgroundColor: s.color,
                    pointBorderColor: surface,
                    pointBorderWidth: 2,
                    // Точки видны всегда: это дни, а не абстрактная кривая.
                    // Сглаживание уменьшено — при tension 0.4 единственное
                    // значение растекалось «холмом» на соседние дни.
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    hitRadius: 14,
                    borderWidth: 2.5,
                    tension: 0.25,
                    fill: true,
                    borderCapStyle: 'round',
                    borderJoinStyle: 'round',
                };
            }),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { top: 4, right: 4, bottom: 0, left: 0 } },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: textColor,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 16,
                        font: { size: 11 },
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(17,24,39,.94)',
                    titleColor: '#f9fafb',
                    bodyColor: '#e5e7eb',
                    footerColor: '#9ca3af',
                    borderColor: 'rgba(255,255,255,.12)',
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 10,
                    displayColors: true,
                    boxPadding: 4,
                    callbacks: {
                        // В подписи набора лежит сумма за период («Заказы · 1»),
                        // а в подсказке нужна только метрика и число за день.
                        label: function (item) {
                            const name = String(item.dataset.label || '').split(' · ')[0];
                            return name + ': ' + item.formattedValue;
                        },
                        footer: function (items) {
                            const sum = items.reduce(function (acc, i) { return acc + (Number(i.raw) || 0); }, 0);
                            return (strings.day_total || 'Всего за день') + ': ' + sum;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { color: gridColor },
                    ticks: { color: textColor, font: { size: 11 }, maxRotation: 0, autoSkipPadding: 12 },
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: suggestedMax,
                    grid: { color: gridColor, drawTicks: false },
                    border: { display: false },
                    // Только целые: событий не бывает «две с половиной».
                    ticks: { color: textColor, font: { size: 11 }, precision: 0, stepSize: step, padding: 8 },
                },
            },
        },
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
            onEnd: function () {
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
