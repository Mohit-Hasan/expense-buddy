<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreCurrencyRequest;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateCurrencyRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Requests\Admin\UpdateSystemSettingsRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrencyManagementService;
use App\Services\SystemSettingService;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $systemSettingService,
        private readonly CurrencyManagementService $currencyManagementService,
        private readonly UserManagementService $userManagementService,
    ) {
    }

    public function settings(): View
    {
        return view('admin.settings', [
            'settings' => $this->systemSettingService->get(),
            'currencies' => Currency::query()->orderBy('name')->get(),
        ]);
    }

    public function updateSettings(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $this->systemSettingService->update(
            $request->validated(),
            $request->file('system_logo')
        );

        return back()->with('success', 'System settings saved.');
    }

    public function currencies(): View
    {
        return view('admin.currencies', [
            'currencies' => Currency::query()->withCount('accounts')->orderBy('name')->get(),
            'baseCurrency' => Currency::query()->where('is_default', true)->first(),
        ]);
    }

    public function storeCurrency(StoreCurrencyRequest $request): RedirectResponse
    {
        try {
            $this->currencyManagementService->create($request->validated());

            return back()->with('success', 'Currency created.');
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['form' => $exception->getMessage()]);
        }
    }

    public function updateCurrency(UpdateCurrencyRequest $request, int $id): RedirectResponse
    {
        try {
            $this->currencyManagementService->update($id, $request->validated());

            return back()->with('success', 'Currency updated.');
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['form' => $exception->getMessage()]);
        }
    }

    public function destroyCurrency(int $id): RedirectResponse
    {
        try {
            $this->currencyManagementService->delete($id);

            return back()->with('success', 'Currency deleted.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()]);
        }
    }

    public function users(Request $request): View
    {
        $query = User::query()->with('role')->orderBy('name');

        if ($request->filled('role_id')) {
            $query->where('role_id', (int) $request->input('role_id'));
        }

        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        return view('admin.users', [
            'users' => $query->get(),
            'roles' => Role::query()->orderBy('name')->get(),
            'filters' => $request->only(['role_id', 'status']),
            'stats' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('status', 'active')->count(),
                'inactive' => User::query()->where('status', 'inactive')->count(),
            ],
        ]);
    }

    public function storeUser(StoreUserRequest $request): RedirectResponse
    {
        $this->userManagementService->createUser($request->validated());

        return back()->with('success', 'User created.');
    }

    public function updateUser(UpdateUserRequest $request, int $id): RedirectResponse
    {
        try {
            $this->userManagementService->updateUser($id, $request->validated());

            return back()->with('success', 'User updated.');
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['form' => $exception->getMessage()]);
        }
    }

    public function destroyUser(int $id): RedirectResponse
    {
        try {
            $this->userManagementService->deleteUser($id);

            return back()->with('success', 'User deleted.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()]);
        }
    }

    public function roles(): View
    {
        $roles = Role::query()->with('permissions')->withCount('users')->orderBy('name')->get();
        $permissionGroups = $this->userManagementService->permissionsGroupedByMenu();

        return view('admin.roles', [
            'roles' => $roles,
            'permissionGroups' => $permissionGroups,
            'menuItems' => \App\Support\MenuPermissionRegistry::items(),
            'stats' => [
                'total' => $roles->count(),
                'permissions' => $permissionGroups->flatten()->count(),
                'assigned' => User::query()->whereNotNull('role_id')->count(),
            ],
        ]);
    }

    public function storeRole(StoreRoleRequest $request): RedirectResponse
    {
        $this->userManagementService->createRole($request->validated());

        return back()->with('success', 'Role created.');
    }

    public function updateRole(UpdateRoleRequest $request, int $id): RedirectResponse
    {
        try {
            $this->userManagementService->updateRole($id, $request->validated());

            return back()->with('success', 'Role updated.');
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['form' => $exception->getMessage()]);
        }
    }

    public function destroyRole(int $id): RedirectResponse
    {
        try {
            $this->userManagementService->deleteRole($id);

            return back()->with('success', 'Role deleted.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()]);
        }
    }

    public function backupDatabase(): StreamedResponse|RedirectResponse
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'mysql' || ! is_string($database) || $database === '') {
            return back()->withErrors(['form' => 'Database backup is only supported for MySQL connections.']);
        }

        $filename = 'ledger_backup_'.now()->format('Y_m_d_His').'.sql';

        return response()->streamDownload(function () use ($database): void {
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_'.$database;

            echo "-- Expense Manager Ledger Backup\n";
            echo '-- Generated at: '.now()->toDateTimeString()."\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $tableName = $table->{$tableKey};
                $createRow = DB::selectOne('SHOW CREATE TABLE `'.$tableName.'`');
                $createSql = $createRow->{'Create Table'} ?? '';

                echo "DROP TABLE IF EXISTS `{$tableName}`;\n";
                echo $createSql.";\n\n";

                $rows = DB::table($tableName)->get();

                foreach ($rows as $row) {
                    $columns = array_keys((array) $row);
                    $values = array_map(function (mixed $value): string {
                        if ($value === null) {
                            return 'NULL';
                        }

                        return "'".str_replace("'", "''", (string) $value)."'";
                    }, array_values((array) $row));

                    echo 'INSERT INTO `'.$tableName.'` (`'.implode('`, `', $columns).'`) VALUES ('.implode(', ', $values).");\n";
                }

                echo "\n";
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }
}
