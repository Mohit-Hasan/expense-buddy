<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\Brand;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

final class TwoFactorService
{
    public function __construct(
        private readonly Google2FA $google2fa,
    ) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $url = $this->google2fa->getQRCodeUrl(
            Brand::appName(),
            $user->email,
            $secret,
        );

        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd(),
        );

        return (new Writer($renderer))->writeString($url);
    }

    public function encryptSecret(string $secret): string
    {
        return Crypt::encryptString($secret);
    }

    public function decryptSecret(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public function verify(User $user, string $code): bool
    {
        $secret = $this->decryptSecret($user->two_factor_secret);

        if ($secret === null) {
            return false;
        }

        return $this->google2fa->verifyKey($secret, $code);
    }

    public function isEnabled(User $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null;
    }
}
