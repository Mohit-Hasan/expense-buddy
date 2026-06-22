@props([
    'type',
])

@php
    use App\Support\TransactionType;

    $class = match ($type) {
        'income' => 'badge-income',
        'expense' => 'badge-expense',
        'transfer' => 'badge-transfer',
        'lending', 'lending_out', 'lending_in', 'lending_repay_in', 'lending_repay_out' => 'badge-lending',
        default => TransactionType::isLending($type) ? 'badge-lending' : 'badge bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    };
    $label = TransactionType::isLending($type) || $type === 'lending'
        ? TransactionType::label($type)
        : ucfirst($type);
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>{{ $label }}</span>
