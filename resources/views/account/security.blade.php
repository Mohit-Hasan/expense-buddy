@extends('layouts.app')

@section('title', 'Account Security')
@section('heading', 'Account Security')
@section('subheading', 'Two-factor authentication and sign-in protection')

@section('content')
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

        <x-panel title="Session" subtitle="Stay signed in without frequent logins">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                When you sign in with “Keep me signed in” checked, your session stays active for up to 30 days on this device.
            </p>
            <ul class="mt-4 list-inside list-disc space-y-2 text-sm text-slate-600 dark:text-slate-400">
                <li>Use a strong, unique password for your account.</li>
                <li>Enable 2FA if you access finances from shared or public devices.</li>
                <li>Sign out when you finish on a device you do not trust.</li>
            </ul>
        </x-panel>
    </div>
@endsection
