<x-auth-layout title="Login" :showPwaBanner="true">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input">
        </div>
        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="text-sm font-medium">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Forgot password?</a>
            </div>
            <input id="password" type="password" name="password" required class="input">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="remember" value="1" checked class="rounded border-slate-300 text-brand-600">
            Keep me signed in for 30 days
        </label>
        <button type="submit" class="btn-primary w-full">Sign In</button>
    </form>
</x-auth-layout>
