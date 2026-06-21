<x-auth-layout title="Two-Factor Authentication">
    <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
        Open your authenticator app and enter the 6-digit code to finish signing in.
    </p>

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="code" class="mb-1.5 block text-sm font-medium">Authentication Code</label>
            <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus class="input font-mono tracking-widest">
        </div>
        <button type="submit" class="btn-primary w-full">Verify</button>
    </form>

    <p class="mt-4 text-center text-sm">
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Back to sign in</a>
    </p>
</x-auth-layout>
