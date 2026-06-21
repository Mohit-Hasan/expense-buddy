@php
    use App\Support\Brand;

    $brandName = Brand::appName($systemSettings);
    $brandLogo = Brand::logoUrl($systemSettings);
@endphp

<div class="flex items-center gap-3">
    @if ($brandLogo)
        <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-9 w-9 rounded-lg object-contain">
    @else
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/30">
            <x-ming-icon name="business.wallet" class="h-5 w-5" />
        </div>
    @endif
    <div>
        <div class="text-lg font-bold tracking-tight text-brand-600 dark:text-brand-400">{{ $brandName }}</div>
        @if ($baseCurrency)
            <div class="text-xs text-slate-500">Base: {{ $baseCurrency->code }}</div>
        @endif
    </div>
</div>
