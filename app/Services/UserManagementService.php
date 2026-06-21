<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UserManagementService
{
    /**
     * @param array<string, mixed> $data
     */
    public function createUser(array $data): User
    {
        return User::query()->create([
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'password' => Hash::make((string) $data['password']),
            'role_id' => (int) $data['role_id'],
            'status' => (string) $data['status'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateUser(int $id, array $data): User
    {
        $user = User::query()->find($id);

        if ($user === null) {
            throw new InvalidArgumentException("User [{$id}] not found.");
        }

        $payload = [
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'role_id' => (int) $data['role_id'],
            'status' => (string) $data['status'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make((string) $data['password']);
        }

        $user->update($payload);

        return $user->fresh(['role']);
    }

    public function deleteUser(int $id): void
    {
        $user = User::query()->find($id);

        if ($user === null) {
            throw new InvalidArgumentException("User [{$id}] not found.");
        }

        if ($user->id === auth()->id()) {
            throw new InvalidArgumentException('You cannot delete your own account.');
        }

        $user->delete();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::query()->create([
                'name' => (string) $data['name'],
                'slug' => (string) $data['slug'],
            ]);

            if (! empty($data['permission_ids'])) {
                $role->permissions()->sync($data['permission_ids']);
            }

            return $role->load('permissions');
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateRole(int $id, array $data): Role
    {
        $role = Role::query()->find($id);

        if ($role === null) {
            throw new InvalidArgumentException("Role [{$id}] not found.");
        }

        return DB::transaction(function () use ($role, $data): Role {
            $role->update([
                'name' => (string) $data['name'],
                'slug' => (string) $data['slug'],
            ]);

            $role->permissions()->sync($data['permission_ids'] ?? []);

            return $role->fresh(['permissions']);
        });
    }

    public function deleteRole(int $id): void
    {
        $role = Role::query()->withCount('users')->find($id);

        if ($role === null) {
            throw new InvalidArgumentException("Role [{$id}] not found.");
        }

        if ($role->users_count > 0) {
            throw new InvalidArgumentException('Role is assigned to users and cannot be deleted.');
        }

        $role->delete();
    }

    public function allPermissions()
    {
        $slugs = collect(\App\Support\MenuPermissionRegistry::permissionDefinitions())->pluck('slug');

        return Permission::query()
            ->whereIn('slug', $slugs)
            ->get()
            ->sortBy(fn (Permission $permission): int => (int) $slugs->search($permission->slug));
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, Permission>>
     */
    public function permissionsGroupedByMenu()
    {
        return $this->allPermissions()->groupBy('group');
    }
}
