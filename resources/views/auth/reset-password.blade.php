<x-auth-layout title="Reset Password">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus class="input">
        </div>
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium">New Password</label>
            <input id="password" type="password" name="password" required class="input">
        </div>
        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required class="input">
        </div>
        <button type="submit" class="btn-primary w-full">Reset Password</button>
    </form>
</x-auth-layout>
