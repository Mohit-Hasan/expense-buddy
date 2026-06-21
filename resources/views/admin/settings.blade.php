@extends('layouts.app')

@section('title', 'System Settings')
@section('heading', 'Administration')
@section('subheading', 'Branding, base currency, and ledger policies')

@section('content')
    @include('admin.partials.nav')

    <div class="grid gap-6 lg:grid-cols-3">
        <x-panel class="lg:col-span-2" title="General Settings" subtitle="Visible branding and ledger rules">
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-1.5 block text-sm font-medium">System Name</label>
                    <input type="text" name="system_name" value="{{ old('system_name', $settings->system_name) }}" class="input" required>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium">System Logo</label>
                    @if ($settings->system_logo)
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ asset('storage/'.$settings->system_logo) }}" alt="Logo" class="h-12 w-12 rounded-xl border object-contain p-1 dark:border-slate-700">
                            <span class="text-xs text-slate-500">Upload a new file to replace</span>
                        </div>
                    @endif
                    <input type="file" name="system_logo" accept="image/*" class="input">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium">Default Base Currency</label>
                    <select name="default_currency_id" class="input" data-search-select data-placeholder="Base currency" data-search-placeholder="Search currencies…" required>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}" @selected((int) old('default_currency_id', $settings->default_currency_id) === $currency->id)>
                                {{ $currency->name }} ({{ $currency->code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Dashboard totals and reports convert other currencies to this base. Account balances stay in their native currency.</p>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="allow_negative_balances" value="1" @checked(old('allow_negative_balances', $settings->allow_negative_balances)) class="rounded border-slate-300 text-brand-600">
                    Allow negative account balances
                </label>

                <button type="submit" class="btn-primary">Save Settings</button>
            </form>
        </x-panel>

        <x-panel title="Preview">
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                @if ($settings->system_logo)
                    <img src="{{ asset('storage/'.$settings->system_logo) }}" alt="" class="h-14 w-14 rounded-xl object-contain">
                @else
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30">
                        <x-ming-icon name="business.wallet" class="h-7 w-7" />
                    </div>
                @endif
                <div>
                    <div class="text-lg font-bold">{{ $settings->system_name }}</div>
                    <div class="text-sm text-slate-500">Base: {{ $settings->defaultCurrency?->code ?? '—' }}</div>
                </div>
            </div>
        </x-panel>
    </div>
@endsection
