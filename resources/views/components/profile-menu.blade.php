@php
    use App\Support\MenuPermissionRegistry;

    $user = auth()->user();
    $adminRoute = MenuPermissionRegistry::firstAdminRouteFor($user);
@endphp

<div class="relative" id="profile-menu">
    <button id="profile-menu-toggle"
            type="button"
            class="flex items-center gap-2 rounded-xl p-1 transition hover:bg-slate-100 dark:hover:bg-slate-800"
            aria-expanded="false"
            aria-haspopup="true"
            aria-controls="profile-menu-panel">
        <x-user-avatar :user="$user" />
        <span class="hidden max-w-[8rem] truncate text-sm font-medium sm:block">{{ $user?->name }}</span>
        <x-ming-icon name="arrow.down" class="hidden h-4 w-4 text-slate-400 sm:block" />
    </button>

    <div id="profile-menu-panel"
         class="absolute right-0 top-[calc(100%+0.5rem)] z-50 hidden w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"
         role="menu"
         aria-labelledby="profile-menu-toggle">
        <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <x-user-avatar :user="$user" size="sm" />
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold">{{ $user?->name }}</div>
                    <div class="truncate text-xs text-slate-500">{{ $user?->email }}</div>
                </div>
            </div>
        </div>

        <div class="py-1">
            <a href="{{ route('account.security') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
               role="menuitem">
                <x-ming-icon name="user.user-security" class="h-4 w-4 text-slate-400" />
                Security
            </a>

            @if ($adminRoute !== null)
                <a href="{{ route($adminRoute) }}"
                   class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                   role="menuitem">
                    <x-ming-icon name="system.settings-1" class="h-4 w-4 text-slate-400" />
                    Administration
                </a>
            @endif
        </div>

        <div class="border-t border-slate-200 py-1 dark:border-slate-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                        role="menuitem">
                    <x-ming-icon name="system.exit" class="h-4 w-4" />
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
