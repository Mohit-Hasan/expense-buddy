@php
    use App\Support\MenuPermissionRegistry;

    $user = auth()->user();
    $sections = MenuPermissionRegistry::visibleSectionsFor($user);
    $adminRoute = MenuPermissionRegistry::firstAdminRouteFor($user);
@endphp

<nav class="space-y-1">
    @if (! empty($sections['Main Menu']))
        @foreach ($sections['Main Menu'] as $item)
            <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}">
                <x-ming-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                <span>{{ $item['name'] }}</span>
            </a>
        @endforeach
    @endif

    @if (! empty($sections['Lending']))
        <div class="px-3 pb-1 pt-5 text-[11px] font-bold uppercase tracking-widest text-slate-400">Lending</div>
        @foreach ($sections['Lending'] as $item)
            <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}">
                <x-ming-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                <span>{{ $item['name'] }}</span>
            </a>
        @endforeach
    @endif

    @if (! empty($sections['Analytics']))
        <div class="px-3 pb-1 pt-5 text-[11px] font-bold uppercase tracking-widest text-slate-400">Analytics</div>
        @foreach ($sections['Analytics'] as $item)
            <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}">
                <x-ming-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                <span>{{ $item['name'] }}</span>
            </a>
        @endforeach
    @endif

    @if ($adminRoute !== null)
        <div class="px-3 pb-1 pt-5 text-[11px] font-bold uppercase tracking-widest text-slate-400">System</div>
        <a href="{{ route($adminRoute) }}" class="nav-link {{ request()->routeIs('admin.*') || request()->routeIs('account.*') ? 'active' : '' }}">
            <x-ming-icon name="system.settings-1" class="h-5 w-5 shrink-0" />
            <span>Administration</span>
        </a>
    @endif
</nav>
