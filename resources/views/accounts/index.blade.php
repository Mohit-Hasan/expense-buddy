@extends('layouts.app')

@section('title', 'Accounts')
@section('heading', 'Multi-Currency Accounts')
@section('subheading', 'Cash positions across currencies — edit details and archive instead of delete')

@section('content')
    @php
        use App\Support\MoneyFormatter;
        $activeAccounts = $accounts->where('status', 'active');
        $grouped = $activeAccounts->groupBy('currency_id');
    @endphp

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Total Accounts" :value="(string) $stats['total']" color="brand" />
        <x-stat-card label="Active" :value="(string) $stats['active']" color="emerald" />
        <x-stat-card label="Archived" :value="(string) $stats['archived']" color="rose" />
    </div>

    @if ($grouped->isNotEmpty())
        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-4">
            <form method="GET" class="card flex flex-wrap items-end gap-3 p-4">
                <div class="min-w-[10rem] flex-1">
                    <label class="label">Status</label>
                    <select name="status" class="input">
                        <option value="">All statuses</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Archived</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="{{ route('accounts.index') }}" class="btn-secondary">Reset</a>
                </div>
            </form>

            @forelse ($accounts as $account)
                <div class="card p-5 transition hover:shadow-card-hover {{ $account->status === 'inactive' ? 'opacity-75' : '' }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold">{{ $account->account_title }}</h3>
                                <x-currency-badge :currency="$account->currency" />
                                <span class="badge {{ $account->status === 'active' ? 'badge-income' : 'badge-expense' }}">
                                    {{ $account->status === 'active' ? 'Active' : 'Archived' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $account->account_number ?? 'No account number' }}</p>
                            @if ($account->note)
                                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $account->note }}</p>
                            @endif
                            <p class="mt-2 text-xs text-slate-400">{{ $account->transactions_count }} transaction(s)</p>
                        </div>
                        <div class="flex flex-col gap-3 sm:items-end">
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
                            <div class="flex flex-wrap gap-2">
                                @if ($account->status === 'active')
                                    <x-form-modal-trigger
                                        :config="[
                                            'title' => 'Edit Account',
                                            'action' => route('accounts.update', $account->id),
                                            'method' => 'PUT',
                                            'fields' => [
                                                'account_title' => $account->account_title,
                                                'account_number' => $account->account_number ?? '',
                                                'currency_id' => $account->currency_id,
                                                'note' => $account->note ?? '',
                                            ],
                                            'readonlyFields' => $account->transactions_count > 0 ? ['currency_id'] : [],
                                        ]"
                                        class="btn-secondary !px-3 !py-1.5 text-xs"
                                    >
                                        Edit
                                    </x-form-modal-trigger>
                                    <button type="button"
                                            data-confirm="Archive &quot;{{ $account->account_title }}&quot;? Existing transactions are kept, but this account will be hidden from new entries."
                                            form="archive-account-{{ $account->id }}"
                                            class="text-xs font-medium text-amber-600 hover:underline">
                                        Archive
                                    </button>
                                @else
                                    <button type="button"
                                            data-confirm="Restore &quot;{{ $account->account_title }}&quot; for use in new transactions?"
                                            form="restore-account-{{ $account->id }}"
                                            class="btn-secondary !px-3 !py-1.5 text-xs">
                                        Restore
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-8 text-center text-sm text-slate-500">No accounts match your filters.</div>
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

    @foreach ($accounts as $account)
        @if ($account->status === 'active')
            <form id="archive-account-{{ $account->id }}" method="POST" action="{{ route('accounts.archive', $account->id) }}" class="hidden">
                @csrf
            </form>
        @else
            <form id="restore-account-{{ $account->id }}" method="POST" action="{{ route('accounts.restore', $account->id) }}" class="hidden">
                @csrf
            </form>
        @endif
    @endforeach

    <x-form-modal wide>
        <form id="form-modal-form" method="POST" action="{{ route('accounts.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Account title</label>
                <input type="text" name="account_title" data-modal-field="account_title" class="input" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Account number</label>
                <input type="text" name="account_number" data-modal-field="account_number" class="input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Currency</label>
                <select name="currency_id" data-modal-field="currency_id" class="input" required>
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->id }}">{{ $currency->name }} ({{ $currency->code }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Currency cannot be changed after transactions are recorded.</p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Notes</label>
                <textarea name="note" rows="2" data-modal-field="note" class="input"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="btn-secondary" data-form-modal-dismiss>Cancel</button>
                <button type="submit" class="btn-primary">Save Account</button>
            </div>
        </form>
    </x-form-modal>
@endsection
