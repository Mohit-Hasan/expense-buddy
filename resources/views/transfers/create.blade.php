@extends('layouts.app')

@section('title', 'Transfer Funds')
@section('heading', 'Transfer Funds')
@section('subheading', 'Move money between your accounts — supports cross-currency')

@section('actions')
    <a href="{{ route('transactions.index') }}" class="btn-secondary">Back to Ledger</a>
@endsection

@section('content')
    <x-section-nav :items="[
        ['route' => 'transactions.index', 'label' => 'All Entries', 'icon' => 'business.bank-card', 'active' => 'transactions.index'],
        ['route' => 'transactions.create', 'label' => 'Record', 'icon' => 'system.add', 'active' => 'transactions.create'],
        ['route' => 'transfers.create', 'label' => 'Transfer', 'icon' => 'arrow.transfer', 'active' => 'transfers.*'],
    ]" />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-panel class="lg:col-span-2" title="Transfer Details" subtitle="Creates a paired debit and credit entry">
            <form method="POST" action="{{ route('transfers.store') }}" class="space-y-4">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">From Account</label>
                        <select name="source_account_id" id="transfer-source" class="input" data-search-select data-placeholder="Select source" data-search-placeholder="Search accounts…" required>
                            <option value="">Select source</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" data-currency-id="{{ $account->currency_id }}" @selected((string) old('source_account_id') === (string) $account->id)>
                                    {{ $account->account_title }} ({{ $account->currency?->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">To Account</label>
                        <select name="destination_account_id" class="input" data-search-select data-placeholder="Select destination" data-search-placeholder="Search accounts…" required>
                            <option value="">Select destination</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('destination_account_id') === (string) $account->id)>
                                    {{ $account->account_title }} ({{ $account->currency?->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Payment Method</label>
                        <select name="payment_method_id" class="input" data-search-select data-placeholder="Payment method" data-search-placeholder="Search methods…" required>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method->id }}" @selected((string) old('payment_method_id') === (string) $method->id)>{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Date</label>
                        <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="input" required>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="label">Currency</label>
                        <select name="currency_id" id="transfer-currency" class="input" data-search-select data-placeholder="Currency" data-search-placeholder="Search currencies…" required>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" data-rate="{{ $currency->exchange_rate }}" @selected((string) old('currency_id', $currencies->first()?->id) === (string) $currency->id)>
                                    {{ $currency->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Amount</label>
                        <input type="number" name="amount" step="0.0001" min="0.0001" value="{{ old('amount') }}" placeholder="0.00" class="input amount-input" required>
                    </div>
                    <div>
                        <label class="label">Exchange Rate</label>
                        <input type="number" name="rate_at_transaction" id="transfer-rate" step="0.0001" min="0.0001" value="{{ old('rate_at_transaction', '1.0000') }}" class="input" required>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="btn-primary">Execute Transfer</button>
                    <a href="{{ route('transactions.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </x-panel>

        <div class="space-y-4">
            <x-panel title="How transfers work">
                <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-400">
                    <li>Transfers move money between <strong>your own accounts</strong> only.</li>
                    <li>Cross-currency transfers use the exchange rate you enter.</li>
                    <li>Two linked entries are created — one debit, one credit.</li>
                    <li>Transfers do not affect lending balances.</li>
                </ul>
            </x-panel>
            <x-panel title="Record income or expense?">
                <p class="text-sm text-slate-600 dark:text-slate-400">External money in/out uses the transaction recorder.</p>
                <a href="{{ route('transactions.create') }}" class="btn-secondary mt-4 w-full">Record Transaction</a>
            </x-panel>
        </div>
    </div>
@endsection
