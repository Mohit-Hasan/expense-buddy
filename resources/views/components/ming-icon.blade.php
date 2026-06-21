@props([
    'name',
    'class' => 'h-5 w-5',
])

{!! svg('mingcute-' . $name, $class)->toHtml() !!}
