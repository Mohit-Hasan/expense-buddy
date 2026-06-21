<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — {{ \App\Support\Brand::appName($systemSettings) }}</title>
    <x-pwa-meta />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-gradient-to-br from-slate-50 via-brand-50/30 to-slate-100 p-4 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
    <div class="w-full max-w-md">
        <div class="card overflow-hidden shadow-card-hover">
            <div class="bg-gradient-to-r from-brand-600 to-brand-700 px-8 py-8 text-white">
                <div class="flex items-center gap-4">
                    @php
                        $brandLogo = \App\Support\Brand::logoUrl($systemSettings);
                    @endphp
                    @if ($brandLogo)
                        <img src="{{ $brandLogo }}" alt="" class="h-12 w-12 rounded-xl bg-white/10 object-contain p-1">
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 text-lg font-bold">EB</div>
                    @endif
                    <div>
                        <h1 class="text-2xl font-bold">{{ \App\Support\Brand::appName($systemSettings) }}</h1>
                        <p class="mt-1 text-sm text-brand-100">{{ \App\Support\Brand::tagline() }}</p>
                    </div>
                </div>
            </div>
            <div class="p-8">
                <x-pwa-install-banner />

                @if (session('success'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input">
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium">Password</label>
                        <input id="password" type="password" name="password" required class="input">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600">
                        Remember me
                    </label>
                    <button type="submit" class="btn-primary w-full">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
