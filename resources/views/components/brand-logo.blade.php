@props([
    'size' => 'md',
    'variant' => 'default',
])

@php
    use App\Support\Brand;

    $sizeClasses = match ($size) {
        'sm' => 'h-9 w-9',
        'lg' => 'h-14 w-14',
        default => 'h-12 w-12',
    };

    $displayUrl = Brand::displayLogoUrl($systemSettings ?? null);

    $imgClasses = match ($variant) {
        'onBrand' => "$sizeClasses rounded-xl object-contain bg-white/10 p-1",
        default => "$sizeClasses rounded-xl object-contain",
    };
@endphp

<img
    {{ $attributes->merge(['class' => $imgClasses, 'alt' => Brand::appName($systemSettings ?? null)]) }}
    src="{{ $displayUrl }}"
>
