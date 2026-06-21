<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\AppInstall;
use App\Support\Brand;
use Database\Seeders\MenuPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class InstallService
{
    /**
     * @param array{
     *     admin_name: string,
     *     admin_email: string,
     *     admin_password: string,
     *     system_name?: string,
     *     currency_name: string,
     *     currency_code: string,
     *     currency_symbol: string,
     *     allow_negative_balances?: bool,
     * } $data
     */
    public function install(array $data, UploadedFile $logo, bool $force = false): void
    {
        if (! $force && AppInstall::isInstalled()) {
            throw new RuntimeException('Application is already installed.');
        }

        if (! file_exists(public_path('storage'))) {
            Artisan::call('storage:link');
        }

        DB::transaction(function () use ($data, $logo): void {
            $adminRole = Role::query()->create([
                'name' => 'Administrator',
                'slug' => 'administrator',
            ]);

            (new MenuPermissionSeeder())->run();

            $currency = Currency::query()->create([
                'name' => $data['currency_name'],
                'code' => strtoupper($data['currency_code']),
                'symbol' => $data['currency_symbol'],
                'exchange_rate' => '1.0000',
                'is_default' => true,
            ]);

            $logoPath = $logo->store('branding', 'public');

            SystemSetting::query()->create([
                'system_name' => $data['system_name'] ?? Brand::name(),
                'system_logo' => $logoPath,
                'default_currency_id' => $currency->id,
                'allow_negative_balances' => (bool) ($data['allow_negative_balances'] ?? false),
            ]);

            User::query()->create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'role_id' => $adminRole->id,
                'status' => 'active',
            ]);
        });

        config(['ledger.allow_negative_balances' => (bool) ($data['allow_negative_balances'] ?? false)]);

        AppInstall::markInstalled();
    }
}
