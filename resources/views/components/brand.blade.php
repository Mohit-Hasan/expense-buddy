<div class="flex items-center gap-3">
    <x-brand-logo size="sm" />
    <div>
        <div class="text-lg font-bold tracking-tight text-brand-600 dark:text-brand-400">{{ \App\Support\Brand::appName($systemSettings) }}</div>
        @if ($baseCurrency)
            <div class="text-xs text-slate-500">Base: {{ $baseCurrency->code }}</div>
        @endif
    </div>
</div>
