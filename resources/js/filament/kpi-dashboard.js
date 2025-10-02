const chartRegistry = new Map();
const palette = ['#38bdf8', '#6366f1', '#22c55e', '#f97316', '#ec4899', '#14b8a6'];

const readCssVariable = (name, fallback) => {
    if (typeof window === 'undefined') {
        return fallback;
    }

    const value = getComputedStyle(document.documentElement).getPropertyValue(name);

    return value?.trim() || fallback;
};

const resolveThemeTokens = () => ({
    textPrimary: readCssVariable('--color-text-primary', '#E6EEF8'),
    textMuted: readCssVariable('--color-text-muted', '#94A3B8'),
    grid: readCssVariable('--chart-grid', 'rgba(230, 238, 248, 0.08)'),
});

const loadChartJs = () =>
    new Promise((resolve, reject) => {
        if (window.Chart) {
            resolve(window.Chart);
            return;
        }

        const scriptId = 'kpi-chartjs-cdn';
        const existing = document.getElementById(scriptId);

        if (existing) {
            existing.addEventListener('load', () => resolve(window.Chart));
            existing.addEventListener('error', reject);
            return;
        }

        const script = document.createElement('script');
        script.id = scriptId;
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
        script.async = true;
        script.addEventListener('load', () => {
            if (window.Chart) {
                resolve(window.Chart);
            } else {
                reject(new Error('Chart.js failed to load'));
            }
        });
        script.addEventListener('error', reject);
        document.head.appendChild(script);
    });

const createFormatters = (locale, useArabicDigits) => {
    const numberingSystem = useArabicDigits ? 'arab' : undefined;

    const numberFormatter = new Intl.NumberFormat(locale, {
        numberingSystem,
        maximumFractionDigits: 0,
    });

    const dateFormatter = new Intl.DateTimeFormat(locale, {
        month: 'short',
        day: 'numeric',
    });

    const dateTimeFormatter = new Intl.DateTimeFormat(locale, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });

    return {
        formatNumber: (value) => numberFormatter.format(value ?? 0),
        formatDateLabel: (value) => dateFormatter.format(new Date(value)),
        formatDateTime: (value) => dateTimeFormatter.format(new Date(value)),
    };
};

const destroyCharts = () => {
    chartRegistry.forEach((instance) => instance.destroy());
    chartRegistry.clear();
};

const pickColor = (key, index) => {
    const normalized = key?.toLowerCase() ?? '';

    switch (normalized) {
        case 'draft':
            return '#38bdf8';
        case 'queued':
            return '#f97316';
        case 'processing':
            return '#6366f1';
        case 'completed':
            return '#22c55e';
        case 'opened':
            return '#f97316';
        case 'resolved':
            return '#22c55e';
        default:
            return palette[index % palette.length];
    }
};

const renderThroughputChart = (container, Chart, payload, formatters, direction, tokens) => {
    const canvas = container.querySelector('canvas[data-chart="throughput"]');

    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');

    if (chartRegistry.has('throughput')) {
        chartRegistry.get('throughput').destroy();
    }

    const datasets = (payload.datasets ?? []).map((dataset, index) => ({
        label: dataset.label,
        data: dataset.data ?? [],
        tension: 0.35,
        borderWidth: 2,
        fill: false,
        borderColor: pickColor(dataset.key, index),
        backgroundColor: pickColor(dataset.key, index),
        pointRadius: 2,
        pointBorderWidth: 2,
        pointHoverRadius: 5,
        pointHoverBorderWidth: 2,
        hitRadius: 8,
    }));

    const options = {
        locale: container.dataset.locale,
        maintainAspectRatio: false,
        responsive: true,
        scales: {
            x: {
                ticks: {
                    callback: (value, index) => formatters.formatDateLabel(payload.labels?.[index]),
                    color: tokens.textMuted,
                },
                reverse: direction === 'rtl',
                grid: {
                    display: false,
                },
            },
            y: {
                ticks: {
                    callback: (value) => formatters.formatNumber(value),
                    color: tokens.textMuted,
                },
                beginAtZero: true,
                grid: {
                    color: tokens.grid,
                    drawBorder: false,
                    drawTicks: false,
                },
            },
        },
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                rtl: direction === 'rtl',
                align: 'center',
                labels: {
                    usePointStyle: true,
                    color: tokens.textMuted,
                    padding: 18,
                    boxWidth: 10,
                },
            },
            tooltip: {
                callbacks: {
                    label: (context) => `${context.dataset.label}: ${formatters.formatNumber(context.parsed.y)}`,
                },
                titleColor: tokens.textPrimary,
                bodyColor: tokens.textPrimary,
            },
        },
    };

    const instance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: payload.labels ?? [],
            datasets,
        },
        options,
    });

    chartRegistry.set('throughput', instance);
};

const renderExceptionsChart = (container, Chart, payload, formatters, direction, tokens) => {
    const canvas = container.querySelector('canvas[data-chart="exceptions"]');

    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');

    if (chartRegistry.has('exceptions')) {
        chartRegistry.get('exceptions').destroy();
    }

    const datasets = (payload.datasets ?? []).map((dataset, index) => ({
        label: dataset.label,
        data: dataset.data ?? [],
        backgroundColor: pickColor(dataset.key, index),
        borderRadius: 8,
        barPercentage: 0.65,
        categoryPercentage: 0.72,
    }));

    const options = {
        locale: container.dataset.locale,
        maintainAspectRatio: false,
        responsive: true,
        scales: {
            x: {
                stacked: true,
                ticks: {
                    callback: (value, index) => formatters.formatDateLabel(payload.labels?.[index]),
                    color: tokens.textMuted,
                },
                reverse: direction === 'rtl',
                grid: {
                    display: false,
                },
            },
            y: {
                stacked: true,
                ticks: {
                    callback: (value) => formatters.formatNumber(value),
                    color: tokens.textMuted,
                },
                beginAtZero: true,
                grid: {
                    color: tokens.grid,
                    drawBorder: false,
                    drawTicks: false,
                },
            },
        },
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                rtl: direction === 'rtl',
                align: 'center',
                labels: {
                    usePointStyle: true,
                    color: tokens.textMuted,
                    padding: 18,
                    boxWidth: 10,
                },
            },
            tooltip: {
                callbacks: {
                    label: (context) => `${context.dataset.label}: ${formatters.formatNumber(context.parsed.y)}`,
                },
                titleColor: tokens.textPrimary,
                bodyColor: tokens.textPrimary,
            },
        },
    };

    const instance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: payload.labels ?? [],
            datasets,
        },
        options,
    });

    chartRegistry.set('exceptions', instance);
};

const updateSnapshot = (container, snapshot, formatters) => {
    const target = container.querySelector('[data-snapshot]');

    if (!target) {
        return;
    }

    target.innerHTML = '';

    (snapshot ?? []).forEach((item) => {
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between text-sm text-gray-600 dark:text-gray-300';
        row.innerHTML = `<span>${item.label}</span><span class="font-semibold">${formatters.formatNumber(item.value ?? 0)}</span>`;
        target.appendChild(row);
    });
};

const updateSla = (container, count, formatters) => {
    const target = container.querySelector('[data-sla-count]');

    if (target) {
        target.textContent = formatters.formatNumber(count ?? 0);
    }
};

const updateUpdatedAt = (container, selector, timestamp, formatters) => {
    const target = container.querySelector(selector);
    const template = container.dataset.updatedTemplate ?? 'Updated {time}';

    if (target) {
        if (timestamp) {
            const rendered = template.replace('{time}', formatters.formatDateTime(timestamp));
            target.textContent = rendered;
            target.classList.remove('hidden');
        } else {
            target.textContent = '';
            target.classList.add('hidden');
        }
    }
};

const toggleEmptyState = (container, hasData) => {
    const empty = container.querySelector('[data-empty-wrapper]');

    if (!empty) {
        return;
    }

    if (hasData) {
        empty.classList.add('hidden');
    } else {
        empty.classList.remove('hidden');
    }
};

const hydrateDashboard = async () => {
    const container = document.querySelector('[data-kpi-dashboard]');

    if (!container) {
        return;
    }

    const { locale, direction, usesArabicDigits } = container.dataset;
    const formatters = createFormatters(locale ?? 'en', usesArabicDigits === '1');

    try {
        const tokens = resolveThemeTokens();

        const [Chart, throughputResponse, exceptionsResponse] = await Promise.all([
            loadChartJs(),
            fetch(container.dataset.throughputEndpoint, { headers: { Accept: 'application/json' } }),
            fetch(container.dataset.exceptionsEndpoint, { headers: { Accept: 'application/json' } }),
        ]);

        if (!throughputResponse.ok || !exceptionsResponse.ok) {
            throw new Error('Unable to load KPI metrics');
        }

        const [throughput, exceptions] = await Promise.all([
            throughputResponse.json(),
            exceptionsResponse.json(),
        ]);

        destroyCharts();

        renderThroughputChart(container, Chart, throughput, formatters, direction, tokens);
        renderExceptionsChart(container, Chart, exceptions, formatters, direction, tokens);

        updateSnapshot(container, exceptions.status_snapshot, formatters);
        updateSla(container, exceptions.sla_breaches, formatters);
        updateUpdatedAt(container, '[data-updated-throughput]', throughput.generated_at, formatters);
        updateUpdatedAt(container, '[data-updated-exceptions]', exceptions.generated_at, formatters);

        const hasThroughputData = (throughput.datasets ?? []).some((dataset) => dataset.data?.some((value) => value > 0));
        const hasExceptionData = (exceptions.datasets ?? []).some((dataset) => dataset.data?.some((value) => value > 0));
        const hasSnapshot = (exceptions.status_snapshot ?? []).some((item) => (item.value ?? 0) > 0);
        const hasSla = (exceptions.sla_breaches ?? 0) > 0;

        toggleEmptyState(container, hasThroughputData || hasExceptionData || hasSnapshot || hasSla);
    } catch (error) {
        console.error('Failed to initialise KPI dashboard', error);
        destroyCharts();
        toggleEmptyState(container, false);
    }
};

document.addEventListener('DOMContentLoaded', hydrateDashboard);
document.addEventListener('livewire:navigated', hydrateDashboard);
