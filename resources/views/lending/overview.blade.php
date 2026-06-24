@extends('layouts.app')

@section('title', 'Lending')
@section('heading', 'Lending Management')
@section('subheading', 'Track money lent, outstanding balances, and repayments')

@section('actions')
    <a href="{{ route('contacts.create') }}" class="btn-primary">
        <x-ming-icon name="user.user-add" class="h-4 w-4" />
        Add Contact
    </a>
@endsection

@section('content')
    @php use App\Support\MoneyFormatter; @endphp

    <x-section-nav :items="[
        ['route' => 'lending.overview', 'label' => 'Overview', 'icon' => 'business.safe-box', 'active' => 'lending.overview'],
        ['route' => 'lending.ledger', 'label' => 'Activity Ledger', 'icon' => 'business.chart-bar', 'active' => 'lending.ledger'],
    ]" />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Total Contacts" :value="(string) $totalPeople" icon="user.group" />
        <x-stat-card label="People" :value="(string) $people->count()" color="emerald" icon="user.user-3" trend="Individuals" />
        <x-stat-card label="Companies" :value="(string) $companies->count()" color="violet" icon="building.building-2" trend="Business entities" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-panel title="Outstanding by Contact" subtitle="Top balances currently lent out">
            <div class="h-72">
                <canvas id="lendingOverviewChart"></canvas>
            </div>
        </x-panel>

        <x-panel title="Summary" subtitle="Portfolio at a glance">
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-800">
                    <span class="shrink-0 text-sm text-slate-500">Total outstanding</span>
                    <span class="amount amount-lg amount-lending">{{ MoneyFormatter::format($overview['summary']['total_outstanding'], $baseCurrency) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4 dark:bg-slate-800">
                    <span class="text-sm text-slate-500">Contacts with balance</span>
                    <span class="text-xl font-bold">{{ $overview['summary']['people_with_balance'] }}</span>
                </div>
                <a href="{{ route('lending.ledger') }}" class="btn-primary w-full">View Activity Ledger</a>
                <a href="{{ route('transactions.create') }}" class="btn-secondary w-full">Record Lending Transaction</a>
            </div>
        </x-panel>
    </div>

    <x-panel class="mt-6" title="Overall Balance Trend" subtitle="Total outstanding across all contacts over time">
        <x-balance-trend-panel
            :chart="$overview['trend_chart']"
            :period="$period"
            label="Total outstanding"
        />
    </x-panel>
@endsection

@push('scripts')
<x-chart-init>
    const colors = window.LedgerCharts.chartColors();
    new Chart(document.getElementById('lendingOverviewChart'), {
        type: 'bar',
        data: {
            labels: @json($overview['chart']['labels']),
            datasets: [{
                label: 'Outstanding',
                data: @json($overview['chart']['values']),
                backgroundColor: colors.palette.map(c => c + '99'),
                borderRadius: 8,
            }],
        },
        options: window.LedgerCharts.baseChartOptions({ scales: { y: { beginAtZero: true } } }),
    });
</x-chart-init>
@endpush
