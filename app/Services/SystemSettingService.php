<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use App\Models\SystemSetting;
use App\Support\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class SystemSettingService
{
    public function __construct(
        private readonly MailConfigService $mailConfigService,
    ) {
    }

    public function get(): SystemSetting
    {
        $settings = SystemSetting::query()->with('defaultCurrency')->first();

        if ($settings === null) {
            $defaultCurrency = Currency::query()->where('is_default', true)->first()
                ?? Currency::query()->orderBy('id')->first();

            $settings = SystemSetting::query()->create([
                'system_name' => Brand::name(),
                'default_currency_id' => $defaultCurrency?->id,
                'allow_negative_balances' => (bool) config('ledger.allow_negative_balances', false),
            ]);
        }

        return $settings->loadMissing('defaultCurrency');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data, ?UploadedFile $logo = null): SystemSetting
    {
        $settings = $this->get();

        if ($logo !== null) {
            if ($settings->system_logo !== null) {
                Storage::disk('public')->delete($settings->system_logo);
            }

            $settings->system_logo = $logo->store('branding', 'public');
        }

        if (isset($data['system_name'])) {
            $settings->system_name = (string) $data['system_name'];
        }

        if (array_key_exists('allow_negative_balances', $data)) {
            $settings->allow_negative_balances = (bool) $data['allow_negative_balances'];
        }

        if (! empty($data['default_currency_id'])) {
            $this->setDefaultCurrency($settings, (int) $data['default_currency_id']);
        }

        if (isset($data['mail_driver'])) {
            $settings->mail_driver = (string) $data['mail_driver'];
        }

        if (array_key_exists('mail_host', $data)) {
            $settings->mail_host = $data['mail_host'] !== null && $data['mail_host'] !== ''
                ? (string) $data['mail_host']
                : null;
        }

        if (array_key_exists('mail_port', $data)) {
            $settings->mail_port = $data['mail_port'] !== null && $data['mail_port'] !== ''
                ? (int) $data['mail_port']
                : null;
        }

        if (array_key_exists('mail_username', $data)) {
            $settings->mail_username = $data['mail_username'] !== null && $data['mail_username'] !== ''
                ? (string) $data['mail_username']
                : null;
        }

        if (! empty($data['mail_password'])) {
            $settings->mail_password = $this->mailConfigService->encryptPassword((string) $data['mail_password']);
        }

        if (array_key_exists('mail_encryption', $data)) {
            $encryption = $data['mail_encryption'];
            $settings->mail_encryption = $encryption !== null && $encryption !== '' && $encryption !== 'none'
                ? (string) $encryption
                : null;
        }

        if (array_key_exists('mail_from_address', $data)) {
            $settings->mail_from_address = $data['mail_from_address'] !== null && $data['mail_from_address'] !== ''
                ? (string) $data['mail_from_address']
                : null;
        }

        if (array_key_exists('mail_from_name', $data)) {
            $settings->mail_from_name = $data['mail_from_name'] !== null && $data['mail_from_name'] !== ''
                ? (string) $data['mail_from_name']
                : null;
        }

        $settings->save();

        config(['ledger.allow_negative_balances' => $settings->allow_negative_balances]);

        return $settings->fresh(['defaultCurrency']);
    }

    private function setDefaultCurrency(SystemSetting $settings, int $currencyId): void
    {
        $currency = Currency::query()->find($currencyId);

        if ($currency === null) {
            throw new InvalidArgumentException("Currency [{$currencyId}] not found.");
        }

        DB::transaction(function () use ($settings, $currency): void {
            Currency::query()->update(['is_default' => false]);
            $currency->update(['is_default' => true, 'exchange_rate' => '1.0000']);
            $settings->default_currency_id = $currency->id;
        });
    }
}
