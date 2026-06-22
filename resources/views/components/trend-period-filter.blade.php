@props([
    'period',
])

@php
    use App\Support\BalanceTrendPeriod;
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }} role="group" aria-label="Trend period">
    @foreach (BalanceTrendPeriod::options() as $key => $label)
        <button
            type="button"
            data-trend-period="{{ $key }}"
            aria-pressed="{{ $period === $key ? 'true' : 'false' }}"
            @class([
                'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium transition disabled:opacity-60',
                'bg-brand-600 text-white shadow-sm' => $period === $key,
                'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700' => $period !== $key,
            ])
        >
            {{ $label }}
        </button>
    @endforeach
</div>
