@props(['currency'])

@if ($currency)
    <span {{ $attributes->merge(['class' => 'currency-pill']) }}>
        <span>{{ $currency->symbol }}</span>
        <span>{{ $currency->code }}</span>
    </span>
@endif
