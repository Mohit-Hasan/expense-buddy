@php
    use App\Support\Brand;

    $appName = Brand::appName($systemSettings);
    $logoUrl = Brand::logoUrl($systemSettings);
@endphp

@if ($logoUrl)
    <link rel="icon" href="{{ $logoUrl }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ $logoUrl }}">
@else
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
@endif
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="#0d9488">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $appName }}">
<meta name="application-name" content="{{ $appName }}">
<meta name="mobile-web-app-capable" content="yes">
