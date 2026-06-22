<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ActiveSessionService;
use App\Services\TwoFactorService;
use App\Services\UserSessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountSecurityController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly ActiveSessionService $activeSessionService,
        private readonly UserSessionManager $userSessionManager,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $pendingSecret = $request->session()->get('two_factor.setup_secret');
        $qrSvg = null;

        if ($pendingSecret !== null && ! $user->hasTwoFactorEnabled()) {
            $qrSvg = $this->twoFactorService->qrCodeSvg($user, (string) $pendingSecret);
        }

        $sessions = $this->activeSessionService->activeForUser($user, $request->session()->getId());

        return view('account.security', [
            'user' => $user,
            'pendingSecret' => $pendingSecret,
            'qrSvg' => $qrSvg,
            'sessions' => $sessions,
        ]);
    }

    public function destroySession(Request $request, string $session): RedirectResponse
    {
        $user = $request->user();

        if (! $this->userSessionManager->logoutDevice($user, $request, $session)) {
            return back()->withErrors(['session' => 'That session could not be logged out.']);
        }

        return back()->with('success', 'The selected session has been logged out.');
    }

    public function logoutOtherSessions(Request $request): RedirectResponse
    {
        $user = $request->user();
        $removed = $this->userSessionManager->logoutOtherDevices($user, $request);

        if ($removed === 0) {
            return back()->with('success', 'No other active sessions were found.');
        }

        return back()->with('success', $removed === 1
            ? '1 other session has been logged out.'
            : "{$removed} other sessions have been logged out.");
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('success', 'Two-factor authentication is already enabled.');
        }

        $secret = $this->twoFactorService->generateSecret();
        $request->session()->put('two_factor.setup_secret', $secret);

        return back()->with('success', 'Scan the QR code with your authenticator app, then enter the code to confirm.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();
        $secret = $request->session()->get('two_factor.setup_secret');

        if ($secret === null) {
            return back()->withErrors(['code' => 'Start setup again to generate a new QR code.']);
        }

        if (! $this->twoFactorService->verifySetup((string) $secret, $request->string('code')->toString())) {
            return back()->withErrors(['code' => 'The verification code is invalid.']);
        }

        $user->forceFill([
            'two_factor_secret' => $this->twoFactorService->encryptSecret((string) $secret),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor.setup_secret');

        return back()->with('success', 'Two-factor authentication is now enabled.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! \Illuminate\Support\Facades\Hash::check($request->string('password')->toString(), $user->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->forget('two_factor.setup_secret');

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }
}
