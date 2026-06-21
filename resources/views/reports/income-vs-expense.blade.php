@extends('layouts.app')

@section('title', 'Income vs Expense')
@section('heading', 'Income vs Expense Analytics')
@section('subheading', 'Month-over-month margins and currency exposure')

@section('content')
    @php use App\Support\MoneyFormatter; @endphp

    @include('reports.partials.nav')

    <div class="grid gap-4 sm:grid-cols-3">
        @php
            $latest = $rows->last();
        @endphp
        <x-stat-card label="Latest Income" :value="$latest ? MoneyFormatter::format($latest->total_income, $baseCurrency) : '—'" color="emerald" />
        <x-stat-card label="Latest Expense" :value="$latest ? MoneyFormatter::format($latest->total_expense, $baseCurrency) : '—'" color="rose" />
        <x-stat-card label="Latest Margin" :value="$latest ? MoneyFormatter::format($latest->net_margin, $baseCurrency) : '—'" color="violet" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-panel class="xl:col-span-2" title="Monthly Performance" subtitle="Stacked income vs expense with net margin line">
            <div class="h-80">
                <canvas id="monthlyPerformanceChart"></canvas>
            </div>
        </x-panel>

        <x-panel title="Currency Exposure" subtitle="Portfolio split by currency (base equivalent)">
            <div class="h-80">
                <canvas id="currencyExposureChart"></canvas>
            </div>
        </x-panel>
    </div>

    <x-panel class="mt-6" title="Detailed Monthly Table">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800">
                        <th class="th">Period</th>
                        <th class="th">Income</th>
                        <th class="th">Expense</th>
                        <th class="th">Net Margin</th>
                        <th class="th">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($rows as $row)
                        @php
                            $marginPct = bccomp($row->total_income, '0', 4) > 0
                                ? bcmul(bcdiv($row->net_margin, $row->total_income, 6), '100', 2)
                                : '0.00';
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/30">
                            <td class="td font-medium">{{ $row->period }}</td>
                            <td class="td font-mono text-emerald-600">{{ MoneyFormatter::format($row->total_income, $baseCurrency) }}</td>
                            <td class="td font-mono text-rose-600">{{ MoneyFormatter::format($row->total_expense, $baseCurrency) }}</td>
                            <td class="td font-mono font-semibold">{{ MoneyFormatter::format($row->net_margin, $baseCurrency) }}</td>
                            <td class="td">
                                <span class="badge {{ (float) $marginPct >= 0 ? 'badge-income' : 'badge-expense' }}">{{ $marginPct }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No report data available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
@endsection

@push('scripts')
<script>
    const colors = window.LedgerCharts.chartColors();

    new Chart(document.getElementById('monthlyPerformanceChart'), {
        type: 'bar',
        data: {
            labels: @json($chart['labels']),
            datasets: [
                { label: 'Income', data: @json($chart['income']), backgroundColor: colors.income + 'cc', borderRadius: 6 },
                { label: 'Expense', data: @json($chart['expense']), backgroundColor: colors.expense + 'cc', borderRadius: 6 },
                { label: 'Net Margin', data: @json($chart['margin']), type: 'line', borderColor: colors.transfer, tension: 0.35, fill: false },
            ],
        },
        options: window.LedgerCharts.baseChartOptions(),
    });

    new Chart(document.getElementById('currencyExposureChart'), {
        type: 'polarArea',
        data: {
            labels: @json(collect($currencyExposure)->map(fn ($r) => $r['currency']?->code ?? 'N/A')->values()),
            datasets: [{
                data: @json(collect($currencyExposure)->pluck('balance_base')->values()),
                backgroundColor: colors.palette.map(c => c + 'bb'),
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: colors.text } } } },
    });
</script>
@endpush
