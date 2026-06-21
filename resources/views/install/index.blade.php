<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Install — {{ $defaultSystemName }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-gradient-to-br from-slate-50 via-brand-50/30 to-slate-100 p-4 py-10 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
    <div class="mx-auto w-full max-w-2xl">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-600 text-2xl font-bold text-white shadow-lg shadow-brand-600/30">
                EB
            </div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">{{ $defaultSystemName }}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $defaultTagline }}</p>
        </div>

        <div class="card overflow-hidden shadow-card-hover">
            <div class="border-b border-slate-200 bg-slate-50/80 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/50">
                <h2 class="text-lg font-semibold">Installation setup</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">No sample banks, accounts, or transactions — only the essentials to get started.</p>
            </div>

            <div class="space-y-6 p-6">
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Server requirements</h3>
                    <ul class="space-y-2">
                        @foreach ($requirements as $requirement)
                            <li class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $requirement['ok'] ? 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200' : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200' }}">
                                <span class="mt-0.5 font-bold">{{ $requirement['ok'] ? '✓' : '!' }}</span>
                                <div>
                                    <div class="font-medium">{{ $requirement['label'] }}</div>
                                    @if (! $requirement['ok'] && $requirement['hint'])
                                        <div class="mt-1 font-mono text-xs opacity-80">{{ $requirement['hint'] }}</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if ($errors->any())
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('install.store') }}" enctype="multipart/form-data" class="space-y-6 {{ $requirementsMet ? '' : 'pointer-events-none opacity-50' }}">
                    @csrf

                    <section class="rounded-xl border border-slate-200 p-5 dark:border-slate-800">
                        <h3 class="mb-4 font-semibold">1. Branding</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="system_name" class="mb-1.5 block text-sm font-medium">App name</label>
                                <input id="system_name" type="text" name="system_name" value="{{ old('system_name', $defaultSystemName) }}" required class="input">
                            </div>
                            <div>
                                <label for="system_logo" class="mb-1.5 block text-sm font-medium">Logo & favicon <span class="text-rose-500">*</span></label>
                                <p class="mb-2 text-xs text-slate-500">Square PNG or JPG recommended. Used for sidebar branding, browser tab icon, and mobile install icon.</p>
                                <input id="system_logo" type="file" name="system_logo" accept="image/*" required class="input">
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 p-5 dark:border-slate-800">
                        <h3 class="mb-4 font-semibold">2. Administrator account</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="admin_name" class="mb-1.5 block text-sm font-medium">Full name</label>
                                <input id="admin_name" type="text" name="admin_name" value="{{ old('admin_name') }}" required class="input">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="admin_email" class="mb-1.5 block text-sm font-medium">Email</label>
                                <input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" required class="input">
                            </div>
                            <div>
                                <label for="admin_password" class="mb-1.5 block text-sm font-medium">Password</label>
                                <input id="admin_password" type="password" name="admin_password" required class="input">
                            </div>
                            <div>
                                <label for="admin_password_confirmation" class="mb-1.5 block text-sm font-medium">Confirm password</label>
                                <input id="admin_password_confirmation" type="password" name="admin_password_confirmation" required class="input">
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 p-5 dark:border-slate-800">
                        <h3 class="mb-4 font-semibold">3. Base currency</h3>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-3">
                                <label for="currency_name" class="mb-1.5 block text-sm font-medium">Currency name</label>
                                <input id="currency_name" type="text" name="currency_name" value="{{ old('currency_name', 'US Dollar') }}" required class="input">
                            </div>
                            <div>
                                <label for="currency_code" class="mb-1.5 block text-sm font-medium">Code</label>
                                <input id="currency_code" type="text" name="currency_code" value="{{ old('currency_code', 'USD') }}" maxlength="3" required class="input uppercase">
                            </div>
                            <div>
                                <label for="currency_symbol" class="mb-1.5 block text-sm font-medium">Symbol</label>
                                <input id="currency_symbol" type="text" name="currency_symbol" value="{{ old('currency_symbol', '$') }}" required class="input">
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 p-5 dark:border-slate-800">
                        <label class="flex items-start gap-3 text-sm">
                            <input type="checkbox" name="allow_negative_balances" value="1" class="mt-1 rounded border-slate-300 text-brand-600" {{ old('allow_negative_balances') ? 'checked' : '' }}>
                            <span>
                                <span class="font-medium">Allow negative account balances</span>
                                <span class="mt-1 block text-slate-500">When disabled, expenses and transfers that exceed available balance are rejected.</span>
                            </span>
                        </label>
                    </section>

                    <button type="submit" class="btn-primary w-full" @disabled(! $requirementsMet)>
                        Install ExpenseBuddy
                    </button>
                </form>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            Need demo data for testing? Run <code class="rounded bg-slate-200 px-1.5 py-0.5 font-mono dark:bg-slate-800">SEED_DEMO_DATA=true php artisan db:seed --class=DemoSeeder</code> on a fresh database.
        </p>
    </div>
</body>
</html>
