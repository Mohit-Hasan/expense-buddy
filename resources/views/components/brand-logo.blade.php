@props([
    'size' => 'md',
])

@php
    use App\Support\Brand;

    $sizeClasses = match ($size) {
        'sm' => 'h-9 w-9',
        'lg' => 'h-14 w-14',
        default => 'h-12 w-12',
    };

    $iconClasses = match ($size) {
        'sm' => 'h-5 w-5',
        'lg' => 'h-7 w-7',
        default => 'h-7 w-7',
    };

    $logoUrl = Brand::logoUrl($systemSettings ?? null);
@endphp

@if ($logoUrl)
    <img {{ $attributes->merge(['class' => "$sizeClasses rounded-xl object-contain"]) }} src="{{ $logoUrl }}" alt="">
@else
    <div {{ $attributes->merge(['class' => "flex $sizeClasses items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30"]) }}>
        <x-ming-icon name="business.wallet" class="{{ $iconClasses }}" />
    </div>
@endif
