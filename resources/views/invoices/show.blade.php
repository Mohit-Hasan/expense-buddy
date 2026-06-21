@extends('layouts.app')

@section('title', 'Invoice '.$invoice->invoice_number)
@section('heading', 'Invoice')
@section('subheading', $invoice->invoice_number)

@section('actions')
    <a href="{{ route('transactions.invoice.pdf', $transaction->id) }}" class="btn-primary">Download PDF</a>
    <button type="button" onclick="window.print()" class="btn-secondary">Print</button>
    <a href="{{ route('transactions.index') }}" class="btn-secondary">Back</a>
@endsection

@section('content')
    @php use App\Support\MoneyFormatter; @endphp

    @include('invoices.partials.body')

    <x-panel class="mt-6" title="Share Public Link" subtitle="Optional expiring URL for this invoice">
        <form method="POST" action="{{ route('transactions.invoice.share', $transaction->id) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="label">Expires in (days)</label>
                <input type="number" name="expires_in_days" value="30" min="1" max="365" class="input w-32">
            </div>
            <button type="submit" class="btn-primary">Generate Public Link</button>
        </form>
        @if ($invoice->is_public && $invoice->isAccessible())
            <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-800">
                <div class="font-medium">Public URL</div>
                <a href="{{ route('invoices.public', $invoice->public_token) }}" class="text-brand-600 break-all" target="_blank">
                    {{ route('invoices.public', $invoice->public_token) }}
                </a>
                @if ($invoice->expires_at)
                    <div class="mt-1 text-xs text-slate-500">Expires {{ $invoice->expires_at->diffForHumans() }}</div>
                @endif
            </div>
        @endif
    </x-panel>
@endsection
