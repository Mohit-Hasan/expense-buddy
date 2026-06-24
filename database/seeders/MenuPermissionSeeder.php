<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Support\MenuPermissionRegistry;
use Illuminate\Database\Seeder;

class MenuPermissionSeeder extends Seeder
{
    public function run(): void
    {
        MenuPermissionRegistry::syncPermissions();

        $allPermissionIds = \App\Models\Permission::query()
            ->whereIn('slug', collect(MenuPermissionRegistry::permissionDefinitions())->pluck('slug'))
            ->pluck('id');

        $adminRole = Role::query()->where('slug', 'administrator')->first();

        if ($adminRole !== null) {
            $adminRole->permissions()->sync($allPermissionIds);
        }

        $managerRole = Role::query()->where('slug', 'manager')->first();

        if ($managerRole !== null) {
            $managerRole->permissions()->sync(
                \App\Models\Permission::query()
                    ->whereIn('slug', [
                        'menu.dashboard',
                        'menu.transactions',
                        'menu.transfers',
                        'menu.accounts',
                        'menu.categories',
                        'menu.payment-methods',
                        'menu.contacts',
                        'menu.lending.overview',
                        'menu.lending.ledger',
                        'menu.reports.detailed',
                        'menu.reports.income-vs-expense',
                        'menu.reports.categorized',
                    ])
                    ->pluck('id')
            );
        }
    }
}
