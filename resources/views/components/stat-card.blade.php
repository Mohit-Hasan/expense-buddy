@props([
    'label',
    'value',
    'trend' => null,
    'icon' => null,
    'color' => 'brand',
])

@php
    $colorClasses = match ($color) {
        'emerald' => 'from-emerald-500/10 to-emerald-500/5 text-emerald-600 dark:text-emerald-400',
        'rose' => 'from-rose-500/10 to-rose-500/5 text-rose-600 dark:text-rose-400',
        'violet' => 'from-violet-500/10 to-violet-500/5 text-violet-600 dark:text-violet-400',
        'amber' => 'from-amber-500/10 to-amber-500/5 text-amber-600 dark:text-amber-400',
        default => 'from-brand-500/10 to-brand-500/5 text-brand-600 dark:text-brand-400',
    };
@endphp

<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-gradient-to-br {{ $colorClasses }} blur-2xl"></div>
    <div class="relative min-w-0">
        <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
            @if ($icon)
                <x-ming-icon :name="$icon" class="h-6 w-6 shrink-0 opacity-80" />
            @endif
        </div>
        <p class="amount amount-lg amount-neutral">{{ $value }}</p>
        @if ($trend)
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $trend }}</p>
        @endif
    </div>
</div>
