@props([
    'chart',
    'period',
    'contactId' => null,
    'label' => 'Outstanding',
])

<div
    data-balance-trend
    data-endpoint="{{ route('lending.trend-chart') }}"
    data-period="{{ $period }}"
    data-contact-id="{{ $contactId }}"
    data-label="{{ $label }}"
    {{ $attributes }}
>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-trend-period-filter :period="$period" />
        <p data-trend-meta class="text-xs text-slate-500 dark:text-slate-400">
            <x-trend-chart-meta :meta="$chart['meta']" />
        </p>
    </div>

    <div class="relative h-72" data-trend-canvas-wrap>
        <div
            data-trend-loading
            class="pointer-events-none absolute inset-0 z-10 hidden flex items-center justify-center rounded-xl bg-white/75 dark:bg-slate-900/75"
            aria-hidden="true"
        >
            <svg class="h-8 w-8 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        </div>

        <canvas
            data-trend-canvas
            @class(['h-full w-full', 'hidden' => count($chart['labels']) === 0])
        ></canvas>

        <p
            data-trend-empty
            @class(['py-10 text-center text-sm text-slate-500', 'hidden' => count($chart['labels']) > 0])
        >
            No linked lending activity for this period.
        </p>
    </div>

    <script type="application/json" data-trend-initial>@json($chart)</script>
</div>
