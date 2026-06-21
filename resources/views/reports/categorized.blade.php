@extends('layouts.app')

@section('title', 'Categorized Breakdown')
@section('heading', 'Category Analytics')
@section('subheading', 'Income and expense allocation by category')

@section('content')
    @php use App\Support\MoneyFormatter; @endphp

    @include('reports.partials.nav')

    <x-panel title="Filters">
        <form method="GET" class="grid gap-3 sm:grid-cols-3">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="input">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="input">
            <button type="submit" class="btn-primary">Apply Range</button>
        </form>
    </x-panel>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-panel title="Income Categories" subtitle="Doughnut + bar view">
            <div class="h-56">
                <canvas id="incomeCategoryChart"></canvas>
            </div>
            <div class="mt-4 h-56">
                <canvas id="incomeCategoryBarChart"></canvas>
            </div>
        </x-panel>
        <x-panel title="Expense Categories" subtitle="Doughnut + bar view">
            <div class="h-56">
                <canvas id="expenseCategoryChart"></canvas>
            </div>
            <div class="mt-4 h-56">
                <canvas id="expenseCategoryBarChart"></canvas>
            </div>
        </x-panel>
    </div>

    <x-panel class="mt-6" title="Category Breakdown">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800">
                        <th class="th">Category</th>
                        <th class="th">Type</th>
                        <th class="th">Amount</th>
                        <th class="th">Share</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @php $grandTotal = $rows->sum(fn ($r) => (float) $r->total_amount) ?: 1; @endphp
                    @forelse ($rows as $row)
                        @php $share = round(((float) $row->total_amount / $grandTotal) * 100, 1); @endphp
                        <tr>
                            <td class="td font-medium">{{ $row->category_name }}</td>
                            <td class="td"><x-transaction-type-badge :type="$row->category_type === 'income' ? 'income' : 'expense'" /></td>
                            <td class="td font-mono">{{ MoneyFormatter::format($row->total_amount, $baseCurrency) }}</td>
                            <td class="td">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                        <div class="h-full rounded-full {{ $row->category_type === 'income' ? 'bg-emerald-500' : 'bg-rose-500' }}" style="width: {{ $share }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-500">{{ $share }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No categorized data found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
@endsection

@push('scripts')
<script>
    const colors = window.LedgerCharts.chartColors();
    const doughnut = (id, labels, values) => new Chart(document.getElementById(id), {
        type: 'doughnut',
        data: { labels, datasets: [{ data: values, backgroundColor: colors.palette, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: colors.text } } } },
    });
    doughnut('incomeCategoryChart', @json($charts['income']['labels']), @json($charts['income']['values']));
    doughnut('expenseCategoryChart', @json($charts['expense']['labels']), @json($charts['expense']['values']));

    const horizontalBar = (id, labels, values, barColor) => new Chart(document.getElementById(id), {
        type: 'bar',
        data: { labels, datasets: [{ data: values, backgroundColor: barColor + 'cc', borderRadius: 4 }] },
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

    horizontalBar('incomeCategoryBarChart', @json($charts['income']['labels']), @json($charts['income']['values']), colors.income);
    horizontalBar('expenseCategoryBarChart', @json($charts['expense']['labels']), @json($charts['expense']['values']), colors.expense);
</script>
@endpush
