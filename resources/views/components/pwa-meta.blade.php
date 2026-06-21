@php
    use App\Support\Brand;

    $appName = Brand::appName($systemSettings);
    $faviconUrl = Brand::displayFaviconUrl($systemSettings);
@endphp

<link rel="icon" href="{{ $faviconUrl }}" type="{{ str_ends_with($faviconUrl, '.svg') ? 'image/svg+xml' : 'image/png' }}">
<link rel="apple-touch-icon" href="{{ Brand::displayLogoUrl($systemSettings) }}">
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="#0d9488">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $appName }}">
<meta name="application-name" content="{{ $appName }}">
<meta name="mobile-web-app-capable" content="yes">
