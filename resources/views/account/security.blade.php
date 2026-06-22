@extends('layouts.app')

@section('title', 'Security')
@section('heading', 'Administration')
@section('subheading', 'Security, sessions, and two-factor authentication')

@section('content')
    @include('admin.partials.nav')

    <div class="grid gap-6 lg:grid-cols-2">
        <x-panel title="Two-Factor Authentication (2FA)" subtitle="Protect your account with an authenticator app">
            @if ($user->hasTwoFactorEnabled())
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                    Two-factor authentication is enabled on your account.
                </div>

                <form method="POST" action="{{ route('account.security.disable') }}" class="space-y-4">
                    @csrf
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Enter your password to turn off two-factor authentication.
                    </p>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium">Password</label>
                        <input id="password" type="password" name="password" required class="input">
                    </div>
                    <button type="submit" class="btn-secondary">Disable 2FA</button>
                </form>
            @elseif ($pendingSecret && $qrSvg)
                <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
                    Scan this QR code with Google Authenticator, Authy, or another TOTP app.
                </p>

                <div class="mb-4 flex justify-center rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800">
                    {!! $qrSvg !!}
                </div>

                <form method="POST" action="{{ route('account.security.confirm') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="code" class="mb-1.5 block text-sm font-medium">Verification Code</label>
                        <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus class="input font-mono tracking-widest">
                    </div>
                    <button type="submit" class="btn-primary">Confirm & Enable</button>
                </form>
            @else
                <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
                    Add an extra layer of security. After enabling, you will enter a code from your phone each time you sign in.
                </p>

                <form method="POST" action="{{ route('account.security.enable') }}">
                    @csrf
                    <button type="submit" class="btn-primary">Enable Two-Factor Authentication</button>
                </form>
            @endif
        </x-panel>

        <x-panel title="Security Tips" subtitle="Keep your account safe">
            <ul class="list-inside list-disc space-y-2 text-sm text-slate-600 dark:text-slate-400">
                <li>Use a strong, unique password for your account.</li>
                <li>Enable 2FA if you access finances from shared or public devices.</li>
                <li>Review active sessions below and sign out of devices you do not recognize.</li>
                <li>When you sign in with “Keep me signed in” checked, your session stays active for up to
                    @php
                        $lifetimeMinutes = (int) config('session.lifetime');
                        $lifetimeLabel = $lifetimeMinutes >= 1440
                            ? round($lifetimeMinutes / 1440).' days'
                            : ($lifetimeMinutes >= 60 ? round($lifetimeMinutes / 60).' hours' : $lifetimeMinutes.' minutes');
                    @endphp
                    {{ $lifetimeLabel }} on that device.
                </li>
            </ul>
        </x-panel>
    </div>

    <x-panel class="mt-6" title="Where You're Logged In" subtitle="Active sessions on your account">
        @php
            $otherSessions = $sessions->filter(fn (array $session): bool => ! $session['is_current']);
        @endphp

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">
                {{ $sessions->count() }} active {{ \Illuminate\Support\Str::plural('session', $sessions->count()) }}
            </p>

            @if ($otherSessions->isNotEmpty())
                <form method="POST" action="{{ route('account.security.sessions.logout-others') }}">
                    @csrf
                    <button type="submit" class="btn-secondary text-sm">Log out of all other sessions</button>
                </form>
            @endif
        </div>

        <div class="space-y-3">
            @forelse ($sessions as $session)
                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800 {{ $session['is_current'] ? 'bg-brand-50 dark:bg-gray-700' : '' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium text-slate-900 dark:text-slate-100">{{ $session['device_label'] }}</p>
                                @if ($session['is_current'])
                                    <span class="inline-flex rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                                        This device
                                    </span>
                                @endif
                            </div>

                            <dl class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-medium text-slate-500 dark:text-slate-100">Last active: </dt>
                                    <dd class="dark:text-slate-100">{{ $session['last_active_at']->diffForHumans() }} · {{ $session['last_active_at']->format('M j, Y g:i A') }}</dd>
                                </div>
                                @if ($session['ip_address'])
                                    <div class="flex flex-wrap gap-x-2">
                                        <dt class="font-medium text-slate-500 dark:text-slate-100">IP address: </dt>
                                        <dd class="font-mono text-xs dark:text-slate-100">{{ $session['ip_address'] }}</dd>
                                    </div>
                                @endif
                                @if ($session['user_agent'])
                                    <div class="flex flex-wrap gap-x-2">
                                        <dt class="font-medium text-slate-500 dark:text-slate-100">User agent: </dt>
                                        <dd class="break-all text-xs text-slate-500 dark:text-slate-100">{{ $session['user_agent'] }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        @unless ($session['is_current'])
                            <form method="POST" action="{{ route('account.security.sessions.destroy', $session['id']) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-secondary !px-3 !py-1.5 text-xs">Log out</button>
                            </form>
                        @endunless
                    </div>
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-800">
                    No active sessions found. Sign in again if you expected to see your current session here.
                </p>
            @endforelse
        </div>
    </x-panel>
@endsection
