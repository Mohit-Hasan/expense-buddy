@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between">
        <div class="text-sm text-slate-500">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </div>
        <div class="flex gap-1">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-300 dark:border-slate-700">Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Prev</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Next</a>
            @else
                <span class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-300 dark:border-slate-700">Next</span>
            @endif
        </div>
    </nav>
@endif
