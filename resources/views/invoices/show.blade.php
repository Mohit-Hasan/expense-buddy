@extends('layouts.app')

@section('title', 'Invoice '.$invoice->invoice_number)
@section('heading', 'Invoice')
@section('subheading', $invoice->invoice_number)

@section('actions')
    <a href="{{ route('transactions.invoice.pdf', $transaction->id) }}" class="btn-primary print:hidden">Download PDF</a>
    <button type="button" onclick="window.print()" class="btn-secondary print:hidden">Print</button>
    <a href="{{ route('transactions.index') }}" class="btn-secondary print:hidden">Back</a>
@endsection

@section('content')
    @php
        use App\Support\MoneyFormatter;
        $linkStatus = $invoice->linkStatus();
    @endphp

    <div class="print:hidden grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Amount" :value="MoneyFormatter::format((string) $transaction->amount, $transaction->currency)" color="brand" />
        <x-stat-card label="Date" :value="$transaction->transaction_date->format('M j, Y')" />
        <x-stat-card :label="ucfirst($transaction->type)" :value="$transaction->category?->name ?? '—'" color="violet" />
        <x-stat-card
            label="Public link"
            :value="match ($linkStatus) {
                'active' => 'Active',
                'expired' => 'Expired',
                default => 'Not shared',
            }"
            :color="match ($linkStatus) {
                'active' => 'emerald',
                'expired' => 'amber',
                default => 'rose',
            }"
        />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            @include('invoices.partials.body')
        </div>

        <div class="space-y-6 print:hidden">
            <x-panel title="Invoice Details" subtitle="Transaction summary">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Invoice #</dt>
                        <dd class="font-mono font-medium">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Payment method</dt>
                        <dd class="font-medium">{{ $transaction->paymentMethod?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Contact</dt>
                        <dd class="font-medium text-right">{{ $transaction->contact?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Account</dt>
                        <dd class="font-medium text-right">{{ $transaction->account?->account_title ?? '—' }}</dd>
                    </div>
                    @if ($transaction->reference)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Reference</dt>
                            <dd class="font-medium text-right">{{ $transaction->reference }}</dd>
                        </div>
                    @endif
                </dl>
            </x-panel>

            <x-panel title="Share Public Link" subtitle="Generate an expiring URL for clients">
                @if ($linkStatus === 'active')
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm dark:border-emerald-900/50 dark:bg-emerald-950/40">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium text-emerald-800 dark:text-emerald-200">Link active</span>
                            @if ($invoice->expires_at)
                                <span class="text-xs text-emerald-700 dark:text-emerald-300">Expires {{ $invoice->expires_at->format('M j, Y') }}</span>
                            @endif
                        </div>
                        <x-copy-link :url="route('invoices.public', $invoice->public_token)" class="mt-2" />
                        <form method="POST" action="{{ route('transactions.invoice.revoke', $transaction->id) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="text-xs font-medium text-rose-600 hover:underline" data-confirm="Revoke this public link? Anyone with the URL will no longer be able to view the invoice.">
                                Revoke link
                            </button>
                        </form>
                    </div>
                @elseif ($linkStatus === 'expired')
                    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200">
                        The previous public link has expired. Generate a new one below.
                    </div>
                @endif

                <form method="POST" action="{{ route('transactions.invoice.share', $transaction->id) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="label">Expires in (days)</label>
                        <input type="number" name="expires_in_days" value="30" min="1" max="365" class="input w-full">
                    </div>
                    <button type="submit" class="btn-primary w-full">
                        {{ $linkStatus === 'active' ? 'Regenerate Link' : 'Generate Public Link' }}
                    </button>
                </form>
                <p class="mt-3 text-xs text-slate-500">Expired links are cleaned up automatically every day.</p>
            </x-panel>
        </div>
    </div>
@endsection
