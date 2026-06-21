<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('login.id');

        if ($userId === null) {
            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = User::query()->find($userId);

        if ($user === null || ! $this->twoFactorService->verify($user, $request->string('code')->toString())) {
            return back()->withErrors(['code' => 'The verification code is invalid.']);
        }

        $remember = (bool) $request->session()->pull('login.remember', true);
        $request->session()->forget('login.id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
