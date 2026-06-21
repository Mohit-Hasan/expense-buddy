@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'card']) }}>
  @if ($title)
    <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-800">
      <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h2>
      @if ($subtitle)
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
      @endif
    </div>
  @endif
  <div class="p-5">
    {{ $slot }}
  </div>
</div>
