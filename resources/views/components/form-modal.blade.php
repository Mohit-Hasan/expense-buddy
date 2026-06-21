@props(['wide' => false])

<div id="form-modal" class="confirm-modal hidden" role="dialog" aria-modal="true" aria-labelledby="form-modal-title">
    <div class="confirm-modal-backdrop" data-form-modal-dismiss></div>
    <div @class([
        'confirm-modal-panel',
        'max-w-2xl' => $wide,
        'max-w-lg' => ! $wide,
    ])>
        <div class="mb-5 flex items-start justify-between gap-4">
            <h3 id="form-modal-title" class="text-lg font-semibold text-slate-900 dark:text-white">Form</h3>
            <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200" data-form-modal-dismiss aria-label="Close">
                <x-ming-icon name="system.close" class="h-5 w-5" />
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
