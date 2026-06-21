<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

final class MailConfigService
{
    public function applyFromSettings(?SystemSetting $settings): void
    {
        if ($settings === null) {
            return;
        }

        $driver = $settings->mail_driver ?: 'smtp';

        Config::set('mail.default', $driver);

        if ($driver === 'smtp') {
            Config::set('mail.mailers.smtp.host', $settings->mail_host ?? '127.0.0.1');
            Config::set('mail.mailers.smtp.port', (int) ($settings->mail_port ?? 587));
            Config::set('mail.mailers.smtp.username', $settings->mail_username);
            Config::set('mail.mailers.smtp.password', $this->decryptPassword($settings->mail_password));
            Config::set('mail.mailers.smtp.encryption', $settings->mail_encryption ?: null);
        }

        if ($settings->mail_from_address) {
            Config::set('mail.from.address', $settings->mail_from_address);
        }

        if ($settings->mail_from_name) {
            Config::set('mail.from.name', $settings->mail_from_name);
        }
    }

    public function encryptPassword(?string $plain): ?string
    {
        if ($plain === null || $plain === '') {
            return null;
        }

        return Crypt::encryptString($plain);
    }

    private function decryptPassword(?string $encrypted): ?string
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
}
