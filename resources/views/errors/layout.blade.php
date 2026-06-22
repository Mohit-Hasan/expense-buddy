<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — {{ config('app.name', 'ExpenseBuddy') }}</title>
    <x-theme-init />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <main class="flex min-h-screen w-full items-center justify-center p-6">
        <div class="card mx-auto w-full max-w-lg p-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300">
                @yield('icon')
            </div>
            <p class="text-sm font-semibold uppercase tracking-wider text-slate-500">@yield('code')</p>
            <h1 class="mt-2 text-2xl font-bold">@yield('heading')</h1>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">@yield('message')</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ url('/') }}" class="btn-primary">Go to dashboard</a>
                <button type="button" onclick="history.back()" class="btn-secondary">Go back</button>
            </div>
        </div>
    </main>
</body>
</html>
