@extends('layouts.app')

@section('title', 'Detailed Analytics')
@section('heading', 'Detailed Analytics')
@section('subheading', 'Category, time, and period breakdowns with interactive charts')

@section('content')
    @php use App\Support\MoneyFormatter; @endphp

    @include('reports.partials.nav')

    <x-panel title="Filters">
        <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Group by</label>
                <select name="group_by" class="input">
                    <option value="day" @selected($filters['group_by'] === 'day')>Daily</option>
                    <option value="week" @selected($filters['group_by'] === 'week')>Weekly</option>
                    <option value="month" @selected($filters['group_by'] === 'month')>Monthly</option>
                </select>
            </div>
            <div class="flex items-end sm:col-span-2 lg:col-span-2">
                <button type="submit" class="btn-primary w-full sm:w-auto">Apply Filters</button>
            </div>
        </form>
    </x-panel>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Income" :value="MoneyFormatter::format($summary['total_income'], $baseCurrency)" color="emerald" />
        <x-stat-card label="Total Expense" :value="MoneyFormatter::format($summary['total_expense'], $baseCurrency)" color="rose" />
        <x-stat-card label="Total Lending" :value="MoneyFormatter::format($summary['total_lending'], $baseCurrency)" color="violet" />
        <x-stat-card label="Net Margin" :value="MoneyFormatter::format($summary['net_margin'], $baseCurrency)" color="brand" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-panel class="xl:col-span-2" title="Time Series" subtitle="Income, expense, and lending over the selected period">
            <div class="h-80">
                <canvas id="timeSeriesChart"></canvas>
            </div>
        </x-panel>

        <x-panel title="Type Breakdown" subtitle="Share of income, expense, and lending">
            <div class="h-80">
                <canvas id="typeBreakdownChart"></canvas>
            </div>
        </x-panel>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-panel title="Income by Category" subtitle="Horizontal bar chart">
            <div class="h-72">
                <canvas id="incomeCategoryBarChart"></canvas>
            </div>
        </x-panel>
        <x-panel title="Expense by Category" subtitle="Horizontal bar chart">
            <div class="h-72">
                <canvas id="expenseCategoryBarChart"></canvas>
            </div>
        </x-panel>
    </div>

    <x-panel class="mt-6" title="Categories Over Time" subtitle="Stacked bars — top categories per period">
        <div class="h-96">
            <canvas id="categoryByPeriodChart"></canvas>
        </div>
    </x-panel>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <x-panel title="Period Breakdown" subtitle="Totals grouped by {{ $filters['group_by'] }}">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800">
                            <th class="th">Period</th>
                            <th class="th">Income</th>
                            <th class="th">Expense</th>
                            <th class="th">Lending</th>
                            <th class="th">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($periodRows as $row)
                            <tr>
                                <td class="td font-medium">{{ $row->period }}</td>
                                <td class="td font-mono text-emerald-600">{{ MoneyFormatter::format($row->total_income, $baseCurrency) }}</td>
                                <td class="td font-mono text-rose-600">{{ MoneyFormatter::format($row->total_expense, $baseCurrency) }}</td>
                                <td class="td font-mono text-violet-600">{{ MoneyFormatter::format($row->total_lending, $baseCurrency) }}</td>
                                <td class="td font-mono font-semibold">{{ MoneyFormatter::format($row->net_margin, $baseCurrency) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No data for this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        <x-panel title="Category Breakdown" subtitle="All categories in range">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800">
                            <th class="th">Category</th>
                            <th class="th">Type</th>
                            <th class="th">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($categoryRows as $row)
                            <tr>
                                <td class="td font-medium">{{ $row->category_name }}</td>
                                <td class="td"><x-transaction-type-badge :type="$row->category_type === 'income' ? 'income' : 'expense'" /></td>
                                <td class="td font-mono">{{ MoneyFormatter::format($row->total_amount, $baseCurrency) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No categorized data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>
    </div>
@endsection

@push('scripts')
<script>
    const colors = window.LedgerCharts.chartColors();

    new Chart(document.getElementById('timeSeriesChart'), {
        type: 'bar',
        data: {
            labels: @json($timeSeries['labels']),
            datasets: [
                { label: 'Income', data: @json($timeSeries['income']), backgroundColor: colors.income + 'cc', borderRadius: 4 },
                { label: 'Expense', data: @json($timeSeries['expense']), backgroundColor: colors.expense + 'cc', borderRadius: 4 },
                { label: 'Lending', data: @json($timeSeries['lending']), backgroundColor: colors.transfer + '99', borderRadius: 4 },
                { label: 'Net Margin', data: @json($timeSeries['margin']), type: 'line', borderColor: colors.palette[1], tension: 0.35, fill: false },
            ],
        },
        options: window.LedgerCharts.baseChartOptions(),
    });

    new Chart(document.getElementById('typeBreakdownChart'), {
        type: 'doughnut',
        data: {
            labels: @json($typeBreakdown['labels']),
            datasets: [{
                data: @json($typeBreakdown['values']),
                backgroundColor: [colors.income, colors.expense, colors.transfer],
                borderWidth: 0,
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: colors.text } } } },
    });

    const horizontalBar = (id, labels, values, barColor) => new Chart(document.getElementById(id), {
        type: 'bar',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: barColor + 'cc', borderRadius: 4 }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: colors.grid }, ticks: { color: colors.text } },
                y: { grid: { display: false }, ticks: { color: colors.text } },
            },
        },
    });

    horizontalBar('incomeCategoryBarChart', @json($categoryBars['income']['labels']), @json($categoryBars['income']['values']), colors.income);
    horizontalBar('expenseCategoryBarChart', @json($categoryBars['expense']['labels']), @json($categoryBars['expense']['values']), colors.expense);

    const stackedDatasets = @json($categoryByPeriod['datasets']).map((dataset, index) => ({
        label: dataset.label,
        data: dataset.data,
        backgroundColor: colors.palette[index % colors.palette.length] + 'cc',
        borderRadius: 2,
    }));

    new Chart(document.getElementById('categoryByPeriodChart'), {
        type: 'bar',
        data: {
            labels: @json($categoryByPeriod['labels']),
            datasets: stackedDatasets,
        },
        options: window.LedgerCharts.baseChartOptions({
            scales: {
                x: { stacked: true, grid: { color: colors.grid }, ticks: { color: colors.text } },
                y: { stacked: true, beginAtZero: true, grid: { color: colors.grid }, ticks: { color: colors.text } },
            },
        }),
    });
</script>
@endpush
