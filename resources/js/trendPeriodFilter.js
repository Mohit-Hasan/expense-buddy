function formatTrendMeta(meta) {
    if (!meta?.transaction_count) {
        return '';
    }

    const points = Number(meta.point_count).toLocaleString();
    const txns = Number(meta.transaction_count).toLocaleString();
    const grouped = meta.point_count < meta.transaction_count ? ` · grouped ${meta.bucket}` : '';

    return `${points} chart ${meta.point_count === 1 ? 'point' : 'points'} from ${txns} ${meta.transaction_count === 1 ? 'transaction' : 'transactions'}${grouped}`;
}

function buildTrendUrl(endpoint, period, contactId) {
    const url = new URL(endpoint, window.location.origin);
    url.searchParams.set('period', period);

    if (contactId) {
        url.searchParams.set('contact_id', contactId);
    }

    return url;
}

function syncTrendUrl(period, contactId) {
    const params = new URLSearchParams(window.location.search);

    if (period === 'lifetime') {
        params.delete('period');
    } else {
        params.set('period', period);
    }

    if (contactId) {
        params.set('contact_id', contactId);
    }

    const query = params.toString();
    const nextUrl = query ? `${window.location.pathname}?${query}` : window.location.pathname;

    window.history.replaceState({}, '', nextUrl);
}

function setActivePeriodButton(root, period) {
    root.querySelectorAll('[data-trend-period]').forEach((button) => {
        const isActive = button.dataset.trendPeriod === period;

        button.classList.toggle('bg-brand-600', isActive);
        button.classList.toggle('text-white', isActive);
        button.classList.toggle('shadow-sm', isActive);
        button.classList.toggle('bg-slate-100', !isActive);
        button.classList.toggle('text-slate-700', !isActive);
        button.classList.toggle('dark:bg-slate-800', !isActive);
        button.classList.toggle('dark:text-slate-200', !isActive);
        button.classList.toggle('dark:hover:bg-slate-700', !isActive);
        button.classList.toggle('hover:bg-slate-200', !isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
}

function createTrendChart(canvas, chartData, label) {
    const colors = window.LedgerCharts.chartColors();

    return new window.Chart(canvas, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label,
                data: chartData.values,
                borderColor: colors.income,
                backgroundColor: `${colors.income}22`,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 6,
            }],
        },
        options: window.LedgerCharts.baseChartOptions(),
    });
}

function toggleTrendEmptyState(root, hasData) {
    const canvas = root.querySelector('[data-trend-canvas]');
    const empty = root.querySelector('[data-trend-empty]');

    canvas?.classList.toggle('hidden', !hasData);
    empty?.classList.toggle('hidden', hasData);
}

function initBalanceTrendPanel(root) {
    const endpoint = root.dataset.endpoint;
    const contactId = root.dataset.contactId || '';
    const label = root.dataset.label || 'Outstanding';
    const initialNode = root.querySelector('[data-trend-initial]');
    const loading = root.querySelector('[data-trend-loading]');
    const meta = root.querySelector('[data-trend-meta]');
    const canvas = root.querySelector('[data-trend-canvas]');

    if (!endpoint || !canvas || !initialNode) {
        return;
    }

    let period = root.dataset.period || 'lifetime';
    let chart = null;

    const applyChartData = (chartData) => {
        const hasData = (chartData.labels?.length ?? 0) > 0;

        toggleTrendEmptyState(root, hasData);
        meta.textContent = formatTrendMeta(chartData.meta);

        if (!hasData) {
            chart?.destroy();
            chart = null;
            return;
        }

        if (!chart) {
            chart = createTrendChart(canvas, chartData, label);
            return;
        }

        chart.data.labels = chartData.labels;
        chart.data.datasets[0].data = chartData.values;
        chart.update();
    };

    try {
        applyChartData(JSON.parse(initialNode.textContent || '{}'));
    } catch {
        applyChartData({ labels: [], values: [], meta: {} });
    }

    root.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-trend-period]');

        if (!button || button.disabled) {
            return;
        }

        event.preventDefault();

        const nextPeriod = button.dataset.trendPeriod;

        if (nextPeriod === period) {
            return;
        }

        period = nextPeriod;
        root.dataset.period = period;
        setActivePeriodButton(root, period);
        syncTrendUrl(period, contactId);

        loading?.classList.remove('hidden');
        root.querySelectorAll('[data-trend-period]').forEach((el) => {
            el.disabled = true;
        });

        try {
            const response = await fetch(buildTrendUrl(endpoint, period, contactId), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load trend data.');
            }

            applyChartData(await response.json());
        } catch {
            meta.textContent = 'Could not refresh chart. Please try again.';
        } finally {
            loading?.classList.add('hidden');
            root.querySelectorAll('[data-trend-period]').forEach((el) => {
                el.disabled = false;
            });
        }
    });
}

export function initBalanceTrendPanels() {
    const boot = () => {
        document.querySelectorAll('[data-balance-trend]').forEach(initBalanceTrendPanel);
    };

    if (typeof window.whenChartsReady === 'function') {
        window.whenChartsReady(boot);
    } else {
        boot();
    }
}
