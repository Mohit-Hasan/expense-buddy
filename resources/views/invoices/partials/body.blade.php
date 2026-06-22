@php use App\Support\MoneyFormatter; @endphp

<div class="card mx-auto max-w-3xl p-8 print:shadow-none print:border-0 print:shadow-none">
    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 print:border-slate-300">
        <div>
            <div class="text-2xl font-bold text-brand-600">{{ $systemName }}</div>
            <div class="mt-1 text-sm text-slate-500">Invoice {{ $invoice->invoice_number }}</div>
        </div>
        <div class="text-right text-sm text-slate-500">
            <div>{{ $transaction->transaction_date->format('F j, Y') }}</div>
            <x-transaction-type-badge :type="$transaction->type" class="mt-2" />
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 text-sm">
        @if ($transaction->contact)
            <div>
                <div class="text-xs font-semibold uppercase text-slate-400">{{ ucfirst($transaction->contact->type) }}</div>
                <div class="mt-1 font-medium">{{ $transaction->contact->name }}</div>
                @if ($transaction->contact->email)
                    <div class="text-slate-500">{{ $transaction->contact->email }}</div>
                @endif
            </div>
        @endif
        @if ($transaction->category)
            <div>
                <div class="text-xs font-semibold uppercase text-slate-400">Category</div>
                <div class="mt-1 font-medium">{{ $transaction->category->name }}</div>
            </div>
        @endif
        @if ($transaction->paymentMethod)
            <div>
                <div class="text-xs font-semibold uppercase text-slate-400">Payment</div>
                <div class="mt-1 font-medium">{{ $transaction->paymentMethod->name }}</div>
            </div>
        @endif
        <div>
            <div class="text-xs font-semibold uppercase text-slate-400">Account</div>
            <div class="mt-1 font-medium">{{ $transaction->account?->account_title }}</div>
            <x-currency-badge :currency="$transaction->currency" class="mt-1" />
        </div>
    </div>

    @if ($transaction->description || $transaction->reference)
        <div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-800 print:bg-transparent print:p-0">
            @if ($transaction->reference)
                <div><span class="text-slate-500">Reference:</span> {{ $transaction->reference }}</div>
            @endif
            @if ($transaction->description)
                <div class="mt-1">{{ $transaction->description }}</div>
            @endif
        </div>
    @endif

    <div class="mt-8 flex items-end justify-between border-t border-slate-200 pt-6 dark:border-slate-800 print:border-slate-300">
        <div class="text-sm text-slate-500">Amount</div>
        <div class="amount amount-lg amount-neutral">{{ MoneyFormatter::format((string) $transaction->amount, $transaction->currency) }}</div>
    </div>
</div>
