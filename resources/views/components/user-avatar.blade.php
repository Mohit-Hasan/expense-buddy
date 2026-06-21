@props(['user' => null, 'size' => 'md'])

@php
    $user ??= auth()->user();
    $initial = '?';

    if ($user?->name) {
        $initial = mb_strtoupper(mb_substr(trim($user->name), 0, 1));
    }

    $sizeClass = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'lg' => 'h-11 w-11 text-base',
        default => 'h-9 w-9 text-sm',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full border border-gray-200 bg-gray-50 font-semibold text-brand-700 ring-2 ring-white dark:border-brand-800/60 dark:bg-gray-900/25 dark:text-brand-300 dark:ring-slate-900 {$sizeClass}"]) }}>
    {{ $initial }}
</span>
