@extends('layouts.app')

@section('title', 'Accounts')
@section('heading', 'Multi-Currency Accounts')
@section('subheading', 'Cash positions across currencies with base equivalents')

@section('content')
    @php
        use App\Support\MoneyFormatter;
        $grouped = $accounts->groupBy('currency_id');
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($grouped as $currencyAccounts)
            @php
                $currency = $currencyAccounts->first()?->currency;
                $nativeTotal = $currencyAccounts->sum(fn ($a) => (float) $a->current_balance);
                $baseTotal = MoneyFormatter::convertToBase((string) $nativeTotal, (string) ($currency?->exchange_rate ?? '1'));
            @endphp
            <x-stat-card
                :label="($currency?->code ?? 'N/A').' Holdings'"
                :value="MoneyFormatter::format((string) $nativeTotal, $currency)"
                :trend="'≈ '.MoneyFormatter::format($baseTotal, $baseCurrency).' base · '.$currencyAccounts->count().' accounts'"
                color="brand"
            />
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-4">
            @forelse ($accounts as $account)
                <div class="card p-5 transition hover:shadow-card-hover">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold">{{ $account->account_title }}</h3>
                                <x-currency-badge :currency="$account->currency" />
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $account->account_number ?? 'No account number' }}</p>
                            @if ($account->note)
                                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $account->note }}</p>
                            @endif
                        </div>
                        <div class="text-left sm:text-right">
                            <div class="amount amount-lg amount-neutral">{{ MoneyFormatter::format((string) $account->current_balance, $account->currency) }}</div>
                            <div class="mt-1 amount amount-sm text-slate-500">
                                ≈ {{ MoneyFormatter::format(
                                    MoneyFormatter::convertToBase((string) $account->current_balance, (string) ($account->currency?->exchange_rate ?? '1')),
                                    $baseCurrency
                                ) }}
                            </div>
                            <div class="mt-1 text-xs text-slate-400">Initial: {{ MoneyFormatter::format((string) $account->initial_balance, $account->currency) }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-8 text-center text-sm text-slate-500">No accounts yet. Create your first account.</div>
            @endforelse
        </div>

        <x-panel title="Create Account" subtitle="Open a new currency account">
            <form method="POST" action="{{ route('accounts.store') }}" class="space-y-3">
                @csrf
                <input type="text" name="account_title" placeholder="Account title" class="input" required>
                <input type="text" name="account_number" placeholder="Account number (optional)" class="input">
                <select name="currency_id" class="input" data-search-select data-placeholder="Select currency" data-search-placeholder="Search currencies…" required>
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->id }}">{{ $currency->name }} ({{ $currency->code }}) — rate {{ $currency->exchange_rate }}</option>
                    @endforeach
                </select>
                <input type="number" name="initial_balance" step="0.0001" min="0" value="0.0000" class="input" required>
                <textarea name="note" rows="2" placeholder="Notes" class="input"></textarea>
                <button type="submit" class="btn-primary w-full">Create Account</button>
            </form>
        </x-panel>
    </div>
@endsection
