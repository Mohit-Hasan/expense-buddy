<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Str;

class MenuPermissionRegistry
{
    /**
     * @return list<array{
     *     group: string,
     *     name: string,
     *     slug: string,
     *     route: string,
     *     active: string,
     *     icon: string,
     *     patterns: list<string>
     * }>
     */
    public static function items(): array
    {
        return [
            [
                'group' => 'Main Menu',
                'name' => 'Dashboard',
                'slug' => 'menu.dashboard',
                'route' => 'dashboard',
                'active' => 'dashboard',
                'icon' => 'device.dashboard',
                'patterns' => ['dashboard'],
            ],
            [
                'group' => 'Main Menu',
                'name' => 'Transactions',
                'slug' => 'menu.transactions',
                'route' => 'transactions.index',
                'active' => 'transactions.*',
                'icon' => 'business.bank-card',
                'patterns' => ['transactions.*'],
            ],
            [
                'group' => 'Main Menu',
                'name' => 'Transfer Funds',
                'slug' => 'menu.transfers',
                'route' => 'transfers.create',
                'active' => 'transfers.*',
                'icon' => 'arrow.transfer',
                'patterns' => ['transfers.*'],
            ],
            [
                'group' => 'Main Menu',
                'name' => 'Accounts',
                'slug' => 'menu.accounts',
                'route' => 'accounts.index',
                'active' => 'accounts.*',
                'icon' => 'building.bank',
                'patterns' => ['accounts.*'],
            ],
            [
                'group' => 'Main Menu',
                'name' => 'Categories',
                'slug' => 'menu.categories',
                'route' => 'categories.index',
                'active' => 'categories.*',
                'icon' => 'design.layout',
                'patterns' => ['categories.*'],
            ],
            [
                'group' => 'Main Menu',
                'name' => 'Payment Methods',
                'slug' => 'menu.payment-methods',
                'route' => 'payment-methods.index',
                'active' => 'payment-methods.*',
                'icon' => 'business.bank-card',
                'patterns' => ['payment-methods.*'],
            ],
            [
                'group' => 'Lending',
                'name' => 'Lending Overview',
                'slug' => 'menu.lending.overview',
                'route' => 'lending.overview',
                'active' => 'lending.overview',
                'icon' => 'business.safe-box',
                'patterns' => ['lending.overview'],
            ],
            [
                'group' => 'Lending',
                'name' => 'Lending Contacts',
                'slug' => 'menu.lending.contacts',
                'route' => 'lending.people.index',
                'active' => 'lending.people.*',
                'icon' => 'user.group',
                'patterns' => ['lending.people.*', 'contacts'],
            ],
            [
                'group' => 'Lending',
                'name' => 'Activity Ledger',
                'slug' => 'menu.lending.ledger',
                'route' => 'lending.ledger',
                'active' => 'lending.ledger',
                'icon' => 'business.chart-bar',
                'patterns' => ['lending.ledger'],
            ],
            [
                'group' => 'Analytics',
                'name' => 'Detailed Analytics',
                'slug' => 'menu.reports.detailed',
                'route' => 'reports.detailed',
                'active' => 'reports.detailed',
                'icon' => 'business.chart-bar',
                'patterns' => ['reports.detailed'],
            ],
            [
                'group' => 'Analytics',
                'name' => 'Income vs Expense',
                'slug' => 'menu.reports.income-vs-expense',
                'route' => 'reports.income-vs-expense',
                'active' => 'reports.income-vs-expense',
                'icon' => 'business.chart',
                'patterns' => ['reports.income-vs-expense'],
            ],
            [
                'group' => 'Analytics',
                'name' => 'Categorized Reports',
                'slug' => 'menu.reports.categorized',
                'route' => 'reports.categorized',
                'active' => 'reports.categorized',
                'icon' => 'business.chart-pie',
                'patterns' => ['reports.categorized'],
            ],
            [
                'group' => 'Administration',
                'name' => 'General Settings',
                'slug' => 'menu.admin.settings',
                'route' => 'admin.settings',
                'active' => 'admin.settings*',
                'icon' => 'system.settings-1',
                'patterns' => ['admin.settings', 'admin.settings.update'],
            ],
            [
                'group' => 'Administration',
                'name' => 'Currencies',
                'slug' => 'menu.admin.currencies',
                'route' => 'admin.currencies',
                'active' => 'admin.currencies*',
                'icon' => 'business.currency-dollar',
                'patterns' => ['admin.currencies', 'admin.currencies.*'],
            ],
            [
                'group' => 'Administration',
                'name' => 'Users',
                'slug' => 'menu.admin.users',
                'route' => 'admin.users',
                'active' => 'admin.users*',
                'icon' => 'user.user-3',
                'patterns' => ['admin.users', 'admin.users.*'],
            ],
            [
                'group' => 'Administration',
                'name' => 'Roles & Permissions',
                'slug' => 'menu.admin.roles',
                'route' => 'admin.roles',
                'active' => 'admin.roles*',
                'icon' => 'user.user-security',
                'patterns' => ['admin.roles', 'admin.roles.*'],
            ],
            [
                'group' => 'Administration',
                'name' => 'Database Backup',
                'slug' => 'menu.admin.backup',
                'route' => 'admin.backup',
                'active' => 'admin.backup',
                'icon' => 'file.download-2',
                'patterns' => ['admin.backup'],
            ],
        ];
    }

    /**
     * @return list<array{name: string, slug: string, group: string}>
     */
    public static function permissionDefinitions(): array
    {
        return collect(self::items())
            ->map(fn (array $item): array => [
                'name' => $item['name'],
                'slug' => $item['slug'],
                'group' => $item['group'],
            ])
            ->unique('slug')
            ->values()
            ->all();
    }

    public static function permissionForRoute(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        if (Str::is('admin.error-insights*', $routeName)) {
            return 'menu.admin.settings';
        }

        foreach (self::items() as $item) {
            foreach ($item['patterns'] as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return $item['slug'];
                }
            }
        }

        return null;
    }

    /**
     * @return list<array{group: string, name: string, slug: string, route: string, active: string, icon: string, patterns: list<string>}>
     */
    public static function visibleItemsFor(?User $user): array
    {
        return array_values(array_filter(
            self::items(),
            fn (array $item): bool => $user?->hasPermission($item['slug']) ?? false,
        ));
    }

    /**
     * @return array<string, list<array{group: string, name: string, slug: string, route: string, active: string, icon: string, patterns: list<string>}>>
     */
    public static function visibleSectionsFor(?User $user): array
    {
        $sections = [];

        foreach (self::visibleItemsFor($user) as $item) {
            $sections[$item['group']][] = $item;
        }

        return $sections;
    }

    public static function firstAdminRouteFor(?User $user): ?string
    {
        foreach (self::items() as $item) {
            if ($item['group'] !== 'Administration') {
                continue;
            }

            if ($user?->hasPermission($item['slug'])) {
                return $item['route'];
            }
        }

        return null;
    }

    public static function syncPermissions(): void
    {
        $definedSlugs = [];

        foreach (self::permissionDefinitions() as $definition) {
            Permission::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'group' => $definition['group'],
                ],
            );

            $definedSlugs[] = $definition['slug'];
        }

        Permission::query()
            ->whereNotIn('slug', $definedSlugs)
            ->delete();
    }
}
