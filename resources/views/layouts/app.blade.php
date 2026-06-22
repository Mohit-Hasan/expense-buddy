<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ \App\Support\Brand::appName($systemSettings) }}</title>
    <x-pwa-meta />
    <x-theme-init />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    <x-chart-queue-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-full bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div id="mobile-sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/50 backdrop-blur-sm print:hidden lg:hidden"></div>

    <aside id="mobile-sidebar" class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full border-r border-slate-200 bg-white transition-transform duration-200 print:hidden dark:border-slate-800 dark:bg-slate-900 lg:hidden">
        <div class="flex h-16 items-center border-b border-slate-200 px-5 dark:border-slate-800">
            <x-brand />
        </div>
        <div class="p-4">
            <x-sidebar-nav />
        </div>
    </aside>

    <div class="flex min-h-full">
        <aside class="hidden w-72 flex-shrink-0 border-r border-slate-200/80 bg-white print:hidden dark:border-slate-800 dark:bg-slate-900 lg:block">
            <div class="sticky top-0 flex h-screen flex-col">
                <div class="flex h-16 items-center border-b border-slate-200/80 px-6 dark:border-slate-800">
                    <x-brand />
                </div>
                <div class="flex-1 overflow-y-auto p-4">
                    <x-sidebar-nav />
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col overflow-x-hidden">
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur print:hidden dark:border-slate-800 dark:bg-slate-900/90">
                <div class="flex min-h-16 items-center justify-between gap-3 px-4 py-2 sm:px-6">
                    <div class="flex min-w-0 flex-1 items-center gap-3 overflow-hidden">
                        <button id="mobile-menu-toggle" type="button" class="btn-secondary shrink-0 !px-3 lg:hidden" aria-label="Open menu">
                            <x-ming-icon name="editor.menu" class="h-5 w-5" />
                        </button>
                        <div class="min-w-0 flex-1 overflow-hidden">
                            <h1 class="truncate text-lg font-semibold leading-tight">@yield('heading', 'Dashboard')</h1>
                            <p class="hidden truncate text-sm leading-tight text-slate-500 dark:text-slate-400 xl:block">@yield('subheading', 'Financial ledger overview')</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-nowrap items-center gap-2 sm:gap-3">
                        <x-record-transaction-btn />
                        @hasSection('actions')
                            <div class="hidden shrink-0 flex-nowrap items-center gap-2 sm:flex">
                                @yield('actions')
                            </div>
                        @endif
                        <button id="theme-toggle" type="button" class="btn-secondary !px-3" aria-label="Toggle theme">
                            <x-ming-icon name="weather.sun" class="h-5 w-5 dark:hidden" />
                            <x-ming-icon name="weather.moon" class="hidden h-5 w-5 dark:block" />
                        </button>
                        <x-profile-menu />
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 pb-24 sm:p-6 sm:pb-24 lg:p-8 lg:pb-8 print:p-0 print:pb-0">
                @if (session('success'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-200">
                        <p>{{ session('success') }}</p>
                        @if (session('invoice_public_url'))
                            <x-copy-link :url="session('invoice_public_url')" class="mt-2" />
                        @endif
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-200">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @hasSection('actions')
                    <div class="mb-5 flex flex-wrap gap-2 sm:hidden">
                        @yield('actions')
                    </div>
                @endif

                @yield('content')
            </main>

            <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-slate-200 bg-white/95 backdrop-blur print:hidden dark:border-slate-800 dark:bg-slate-900/95 lg:hidden" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 py-3 text-xs {{ request()->routeIs('dashboard') ? 'text-brand-600' : 'text-slate-500' }}">
                    <x-ming-icon name="device.dashboard" class="h-5 w-5" /> Home
                </a>
                <a href="{{ route('transactions.index') }}" class="flex flex-col items-center gap-1 py-3 text-xs {{ request()->routeIs('transactions.*') ? 'text-brand-600' : 'text-slate-500' }}">
                    <x-ming-icon name="business.bank-card" class="h-5 w-5" /> Txns
                </a>
                <a href="{{ route('transfers.create') }}" class="flex flex-col items-center gap-1 py-3 text-xs {{ request()->routeIs('transfers.*') ? 'text-brand-600' : 'text-slate-500' }}">
                    <x-ming-icon name="arrow.transfer" class="h-5 w-5" /> Transfer
                </a>
                <a href="{{ route('lending.overview') }}" class="flex flex-col items-center gap-1 py-3 text-xs {{ request()->routeIs('lending.*') ? 'text-brand-600' : 'text-slate-500' }}">
                    <x-ming-icon name="business.safe-box" class="h-5 w-5" /> Lending
                </a>
                <a href="{{ route('accounts.index') }}" class="flex flex-col items-center gap-1 py-3 text-xs {{ request()->routeIs('accounts.*') ? 'text-brand-600' : 'text-slate-500' }}">
                    <x-ming-icon name="building.bank" class="h-5 w-5" /> Accounts
                </a>
            </nav>
        </div>
    </div>

    @stack('scripts')
    <x-confirm-modal />
</body>
</html>
