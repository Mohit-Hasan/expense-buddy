@props(['items'])

<nav {{ $attributes->merge(['class' => 'mb-6 -mx-4 flex gap-2 overflow-x-auto border-b border-slate-200 px-4 pb-4 dark:border-slate-800 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0']) }}>
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}"
           class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition {{ request()->routeIs($item['active'] ?? $item['route']) ? 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}">
            @if (! empty($item['icon']))
                <x-ming-icon :name="$item['icon']" class="h-4 w-4" />
            @endif
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
