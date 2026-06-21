<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Expense Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-gradient-to-br from-slate-50 via-brand-50/30 to-slate-100 p-4 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
    <div class="w-full max-w-md">
        <div class="card overflow-hidden shadow-card-hover">
            <div class="bg-gradient-to-r from-brand-600 to-brand-700 px-8 py-8 text-white">
                <h1 class="text-2xl font-bold">{{ $systemSettings?->system_name ?? 'Ledger Engine' }}</h1>
                <p class="mt-1 text-sm text-brand-100">Multi-currency income & expense management</p>
            </div>
            <div class="p-8">
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
                <p class="mt-6 text-center text-xs text-slate-400">Demo: admin@ledger.local / password</p>
            </div>
        </div>
    </div>
</body>
</html>
