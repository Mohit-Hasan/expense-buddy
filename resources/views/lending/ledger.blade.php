@extends('layouts.app')

@section('title', 'Activity Ledger')
@section('heading', 'Activity Ledger')
@section('subheading', $selectedContact ? 'Activity for '.$selectedContact->name : 'Overall lending activity across all contacts')

@section('actions')
    @if ($selectedContact)
        <a href="{{ route('lending.ledger') }}" class="btn-secondary">View Overall</a>
    @endif
    <a href="{{ route('lending.people.create') }}" class="btn-secondary">Add Contact</a>
@endsection

@section('content')
    @php
        use App\Support\MoneyFormatter;
        use App\Support\TransactionType;
    @endphp

    <x-section-nav :items="[
        ['route' => 'lending.overview', 'label' => 'Overview', 'icon' => 'business.safe-box', 'active' => 'lending.overview'],
        ['route' => 'lending.people.index', 'label' => 'Contacts', 'icon' => 'user.group', 'active' => 'lending.people.*'],
        ['route' => 'lending.ledger', 'label' => 'Activity Ledger', 'icon' => 'business.chart-bar', 'active' => 'lending.ledger'],
    ]" />

    <x-panel title="Filter by Contact" subtitle="Leave empty for overall view, or pick one contact">
        <form method="GET" action="{{ route('lending.ledger') }}" class="flex flex-wrap items-end gap-3">
            @if ($period !== \App\Support\BalanceTrendPeriod::LIFETIME)
                <input type="hidden" name="period" value="{{ $period }}">
            @endif
            <div class="min-w-[240px] flex-1">
                <label class="label">Contact</label>
                <select name="contact_id" class="input" data-search-select data-placeholder="All contacts (overall)" data-search-placeholder="Search contacts…">
                    <option value="">All contacts — overall view</option>
                    @foreach ($contacts as $contact)
                        <option value="{{ $contact->id }}" @selected($selectedContactId === $contact->id)>
                            {{ $contact->name }} ({{ ucfirst($contact->type) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary">Apply</button>
        </form>
    </x-panel>

    @if ($selectedContact)
        <div class="mt-4 rounded-2xl border border-brand-200/80 bg-brand-50/50 px-5 py-4 dark:border-brand-900/40 dark:bg-brand-950/30">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-lg font-semibold">{{ $selectedContact->name }}</div>
                    <div class="text-sm text-slate-500 dark:text-slate-100">{{ ucfirst($selectedContact->type) }} · Outstanding: {{ MoneyFormatter::format((string) $selectedContact->current_balance, $baseCurrency) }}</div>
                </div>
                <a href="{{ route('lending.people.edit', $selectedContact->id) }}" class="btn-secondary text-sm">Edit</a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Linked Transactions" :value="$summary['transaction_count']" icon="editor.hashtag" />
            <x-stat-card label="Total Volume" :value="MoneyFormatter::format($summary['total_volume'], $baseCurrency)" color="violet" />
            <x-stat-card label="Outstanding Balance" :value="MoneyFormatter::format($summary['current_balance'], $baseCurrency)" color="brand" />
        </div>

        <x-panel class="mt-6" title="Balance Trend">
            <x-balance-trend-panel
                :chart="$chart"
                :period="$period"
                :contact-id="$selectedContactId"
                label="Outstanding"
            />
        </x-panel>
    @else
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
                        <a href="{{ route('lending.ledger', ['contact_id' => $contact->id]) }}" class="person-card">
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
    @endif

    <x-panel class="mt-6" title="{{ $selectedContact ? 'Contact Activity' : 'Recent Linked Transactions' }}">
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800">
                        <th class="th">Date</th>
                        @unless ($selectedContact)<th class="th">Contact</th>@endunless
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
                            @unless ($selectedContact)
                                <td class="td">
                                    @if ($transaction->contact)
                                        <a href="{{ route('lending.ledger', ['contact_id' => $transaction->contact_id]) }}" class="text-brand-600 hover:underline">{{ $transaction->contact->name }}</a>
                                    @else — @endif
                                </td>
                            @endunless
                            <td class="td"><x-transaction-type-badge :type="$transaction->type" /></td>
                            <td class="td">{{ $transaction->account?->account_title }}</td>
                            <td class="td text-right">
                                <span class="amount {{ TransactionType::isLending($transaction->type) ? 'amount-lending' : ($transaction->type === 'income' ? 'amount-income' : 'amount-expense') }}">
                                    {{ MoneyFormatter::format((string) $transaction->amount, $transaction->currency) }}
                                </span>
                            </td>
                            <td class="td text-slate-500">{{ $transaction->description ?? $transaction->reference ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $selectedContact ? 5 : 6 }}" class="px-4 py-12 text-center text-sm text-slate-500">No linked transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
@endsection

@push('scripts')
<x-chart-init>
    const colors = window.LedgerCharts.chartColors();
    @if (!$selectedContact && $overview)
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
