@extends('layouts.app')

@section('title', 'Currencies')
@section('heading', 'Currency Management')
@section('subheading', 'Add currencies and set exchange rates relative to the base currency')

@section('content')
    @include('admin.partials.nav')

    <x-panel title="How Multi-Currency Works">
        <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-400">
            <li>Each account stores balances in its own currency — balances are never auto-converted.</li>
            <li>Exchange rates express how many units of a currency equal <strong>1 unit of the base currency</strong> (e.g. BDT 110 = 110 BDT per 1 USD).</li>
            <li>Dashboard and reports convert to base for analytics only.</li>
        </ul>
    </x-panel>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-4">
            @foreach ($currencies as $currency)
                <x-panel>
                    <form method="POST" action="{{ route('admin.currencies.update', $currency->id) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <x-currency-badge :currency="$currency" />
                                @if ($currency->is_default)
                                    <span class="badge badge-income">Base Currency</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-500">{{ $currency->accounts_count }} account(s)</span>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input type="text" name="name" value="{{ $currency->name }}" class="input" required>
                            <input type="text" name="code" value="{{ $currency->code }}" class="input" required>
                            <input type="text" name="symbol" value="{{ $currency->symbol }}" class="input" required>
                            <input type="number" name="exchange_rate" step="0.0001" min="0.0001" value="{{ $currency->exchange_rate }}" class="input" {{ $currency->is_default ? 'readonly' : 'required' }}>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_default" value="1" @checked($currency->is_default)>
                            Set as base currency
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn-primary">Update</button>
                            @if (! $currency->is_default && $currency->accounts_count === 0)
                                <button type="button" data-confirm="Delete this currency?" form="delete-currency-{{ $currency->id }}" class="btn-secondary text-rose-600">Delete</button>
                            @endif
                        </div>
                    </form>
                    @if (! $currency->is_default && $currency->accounts_count === 0)
                        <form id="delete-currency-{{ $currency->id }}" method="POST" action="{{ route('admin.currencies.destroy', $currency->id) }}" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </x-panel>
            @endforeach
        </div>

        <x-panel title="Add Currency">
            <form method="POST" action="{{ route('admin.currencies.store') }}" class="space-y-3">
                @csrf
                <input type="text" name="name" placeholder="Currency name" class="input" required>
                <input type="text" name="code" placeholder="Code (USD)" class="input" required>
                <input type="text" name="symbol" placeholder="Symbol ($)" class="input" required>
                <input type="number" name="exchange_rate" step="0.0001" min="0.0001" placeholder="Rate vs base" class="input" required>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_default" value="1">
                    Make base currency
                </label>
                <button type="submit" class="btn-primary w-full">Create Currency</button>
            </form>
        </x-panel>
    </div>
@endsection
