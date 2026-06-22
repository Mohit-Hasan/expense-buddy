@props(['meta'])

@php use Illuminate\Support\Str; @endphp

@if (($meta['transaction_count'] ?? 0) > 0)
    {{ $meta['point_count'] }} chart {{ Str::plural('point', $meta['point_count']) }}
    from {{ number_format($meta['transaction_count']) }} {{ Str::plural('transaction', $meta['transaction_count']) }}
    @if ($meta['point_count'] < $meta['transaction_count'])
        · grouped {{ $meta['bucket'] }}
    @endif
@endif
