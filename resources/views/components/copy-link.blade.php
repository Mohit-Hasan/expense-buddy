@props(['url'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <a href="{{ $url }}" class="min-w-0 flex-1 break-all text-brand-600 hover:underline" target="_blank" rel="noopener">
        {{ $url }}
    </a>
    <button
        type="button"
        data-copy="{{ $url }}"
        class="btn-secondary shrink-0 !p-2"
        aria-label="Copy link"
        title="Copy link"
    >
        <span data-copy-icon>
            <x-ming-icon name="file.copy" class="h-4 w-4" />
        </span>
        <span data-copy-feedback class="hidden px-1 text-xs font-medium text-emerald-600">Copied</span>
    </button>
</div>
