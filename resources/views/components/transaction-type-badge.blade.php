@props([
    'type',
])

@php
    $class = match ($type) {
        'income' => 'badge-income',
        'expense' => 'badge-expense',
        'transfer' => 'badge-transfer',
        'lending' => 'badge-lending',
        default => 'badge bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    };
    $label = match ($type) {
        'lending' => 'Lending',
        default => ucfirst($type),
    };
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>{{ $label }}</span>
