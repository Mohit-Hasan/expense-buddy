@props(['config' => []])

<button
    type="button"
    {{ $attributes->merge([
        'data-form-modal-open' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
    ]) }}
>
    {{ $slot }}
</button>
