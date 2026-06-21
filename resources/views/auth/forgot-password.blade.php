<x-auth-layout title="Forgot Password">
    <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
        Enter your email address and we will send you a link to reset your password.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input">
        </div>
        <button type="submit" class="btn-primary w-full">Send Reset Link</button>
    </form>

    <p class="mt-4 text-center text-sm">
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Back to sign in</a>
    </p>
</x-auth-layout>
