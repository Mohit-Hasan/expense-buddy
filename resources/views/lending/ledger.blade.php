@extends('layouts.app')

@section('title', 'Activity Ledger')
@section('heading', 'Activity Ledger')
@section('subheading', 'Overall lending activity across all contacts')

@section('actions')
    <a href="{{ route('contacts.create') }}" class="btn-secondary">Add Contact</a>
@endsection

@section('content')
    @php
        use App\Support\MoneyFormatter;
        use App\Support\TransactionType;
    @endphp

    <x-section-nav :items="[
        ['route' => 'lending.overview', 'label' => 'Overview', 'icon' => 'business.safe-box', 'active' => 'lending.overview'],
        ['route' => 'lending.ledger', 'label' => 'Activity Ledger', 'icon' => 'business.chart-bar', 'active' => 'lending.ledger'],
    ]" />

    <x-panel title="View Contact Activity" subtitle="Pick a contact to see income, expenses, and lending in one place">
        <form method="GET" action="{{ route('lending.ledger') }}" class="flex flex-wrap items-end gap-3">
            @if ($period !== \App\Support\BalanceTrendPeriod::LIFETIME)
                <input type="hidden" name="period" value="{{ $period }}">
            @endif
            <div class="min-w-[240px] flex-1">
                <label class="label">Contact</label>
                <select name="contact_id" class="input" data-search-select data-placeholder="Select a contact…" data-search-placeholder="Search contacts…">
                    <option value="">Select a contact…</option>
                    @foreach ($contacts as $contact)
                        <option value="{{ $contact->id }}">
                            {{ $contact->name }} ({{ ucfirst($contact->type) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary">View Activity</button>
        </form>
    </x-panel>

    <div class="mt-6 grid gap-4 sm:grid-cols-4">
        <x-stat-card label="Total Outstanding" :value="MoneyFormatter::format($summary['total_outstanding'], $baseCurrency)" color="amber" icon="business.safe-box" />
        <x-stat-card label="With Balance" :value="(string) $summary['people_with_balance']" icon="user.group" />
        <x-stat-card label="Total Contacts" :value="(string) $summary['total_people']" color="violet" />
        <x-stat-card label="Recent Activity" :value="(string) $summary['recent_activity_count']" icon="editor.hashtag" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-panel title="Outstanding by Contact">
            <div class="h-72"><canvas id="overallBalanceChart"></canvas></div>
        </x-panel>
        <x-panel title="All Contacts">
            <div class="max-h-72 space-y-2 overflow-y-auto">
                @forelse ($overview['contacts'] as $contact)
                    <a href="{{ route('contacts.show', $contact->id) }}" class="person-card">
                        <div>
                            <div class="font-medium">{{ $contact->name }}</div>
                            <div class="text-xs text-slate-500">{{ ucfirst($contact->type) }}</div>
                        </div>
                        <span class="amount amount-lending">{{ MoneyFormatter::format((string) $contact->current_balance, $baseCurrency) }}</span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">No contacts yet.</p>
                @endforelse
            </div>
        </x-panel>
    </div>

    <x-panel class="mt-6" title="Recent Lending Transactions">
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800">
                        <th class="th">Date</th>
                        <th class="th">Contact</th>
                        <th class="th">Type</th>
                        <th class="th">Account</th>
                        <th class="th text-right">Amount</th>
                        <th class="th">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($transactions as $transaction)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/30">
                            <td class="td whitespace-nowrap">{{ $transaction->transaction_date->format('M d, Y') }}</td>
                            <td class="td">
                                @if ($transaction->contact)
                                    <a href="{{ route('contacts.show', $transaction->contact_id) }}" class="text-brand-600 hover:underline">{{ $transaction->contact->name }}</a>
                                @else — @endif
                            </td>
                            <td class="td"><x-transaction-type-badge :type="$transaction->type" /></td>
                            <td class="td">{{ $transaction->account?->account_title }}</td>
                            <td class="td text-right">
                                <span class="amount amount-lending">
                                    {{ MoneyFormatter::format((string) $transaction->amount, $transaction->currency) }}
                                </span>
                            </td>
                            <td class="td text-slate-500">{{ $transaction->description ?? $transaction->reference ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">No lending transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
@endsection

@push('scripts')
<x-chart-init>
    const colors = window.LedgerCharts.chartColors();
    @if ($overview)
    new Chart(document.getElementById('overallBalanceChart'), {
        type: 'bar',
        data: {
            labels: @json($chart['labels']),
            datasets: [{ label: 'Outstanding', data: @json($chart['values']), backgroundColor: colors.palette.map(c => c + '99'), borderRadius: 8 }],
        },
        options: window.LedgerCharts.baseChartOptions(),
    });
    @endif
</x-chart-init>
@endpush
