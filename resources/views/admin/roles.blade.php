@extends('layouts.app')

@section('title', 'Roles & Permissions')
@section('heading', 'Roles & Permissions')
@section('subheading', 'Control sidebar and page access menu by menu')

@section('actions')
    <x-form-modal-trigger
        :config="[
            'title' => 'Create Role',
            'action' => route('admin.roles.store'),
            'method' => 'POST',
            'fields' => ['name' => '', 'slug' => '', 'permission_ids' => []],
            'requiredFields' => ['name', 'slug'],
        ]"
        class="btn-primary"
    >
        <x-ming-icon name="system.add" class="h-4 w-4" />
        Add Role
    </x-form-modal-trigger>
@endsection

@section('content')
    @include('admin.partials.nav')

    <x-panel title="How Menu Permissions Work">
        <p class="text-sm text-slate-600 dark:text-slate-400">
            Each checkbox maps to a sidebar menu item. Users only see menus they are allowed to access, and direct URL visits are blocked with a 403 error.
        </p>
    </x-panel>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Total Roles" :value="(string) $stats['total']" color="brand" />
        <x-stat-card label="Menu Permissions" :value="(string) $stats['permissions']" color="violet" />
        <x-stat-card label="Users Assigned" :value="(string) $stats['assigned']" color="emerald" />
    </div>

    <x-panel class="mt-6" title="Role List" :subtitle="$roles->count().' roles'">
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800">
                        <th class="th">Name</th>
                        <th class="th">Slug</th>
                        <th class="th">Menu Access</th>
                        <th class="th text-right">Users</th>
                        <th class="th text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($roles as $role)
                        @php
                            $roleGroups = $role->permissions->groupBy('group');
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/30">
                            <td class="td font-medium">
                                {{ $role->name }}
                                @if ($role->slug === 'administrator')
                                    <span class="badge badge-income ml-2">Full Access</span>
                                @endif
                            </td>
                            <td class="td font-mono text-sm text-slate-500">{{ $role->slug }}</td>
                            <td class="td">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($roleGroups as $group => $permissions)
                                        <span class="badge badge-income" title="{{ $permissions->pluck('name')->join(', ') }}">
                                            {{ $group }} ({{ $permissions->count() }})
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400">No menus assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="td text-right font-mono text-sm">{{ $role->users_count }}</td>
                            <td class="td">
                                <div class="flex justify-end gap-2">
                                    <x-form-modal-trigger
                                        :config="[
                                            'title' => 'Edit Role',
                                            'action' => route('admin.roles.update', $role->id),
                                            'method' => 'PUT',
                                            'fields' => [
                                                'name' => $role->name,
                                                'slug' => $role->slug,
                                                'permission_ids' => $role->permissions->pluck('id')->values()->all(),
                                            ],
                                            'requiredFields' => ['name', 'slug'],
                                            'readonlyFields' => $role->slug === 'administrator' ? ['slug'] : [],
                                        ]"
                                        class="btn-secondary !px-3 !py-1.5 text-xs"
                                    >
                                        Edit
                                    </x-form-modal-trigger>
                                    @if ($role->users_count === 0 && $role->slug !== 'administrator')
                                        <button type="button"
                                                data-confirm="Delete role &quot;{{ $role->name }}&quot;? This cannot be undone."
                                                form="delete-role-{{ $role->id }}"
                                                class="text-xs font-medium text-rose-600 hover:underline">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                                No roles defined yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>

    @foreach ($roles as $role)
        @if ($role->users_count === 0 && $role->slug !== 'administrator')
            <form id="delete-role-{{ $role->id }}" method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach

    <x-form-modal wide>
        <form id="form-modal-form" method="POST" action="{{ route('admin.roles.store') }}" class="space-y-4">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Role name</label>
                    <input type="text" name="name" data-modal-field="name" data-modal-required="name" class="input" placeholder="Role name" required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Slug</label>
                    <input type="text" name="slug" data-modal-field="slug" data-modal-required="slug" class="input" placeholder="slug-format" pattern="[a-z0-9\-]+" required>
                </div>
            </div>

            <div class="max-h-[28rem] space-y-5 overflow-y-auto pr-1">
                @foreach ($permissionGroups as $group => $permissions)
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $group }}</h4>
                            <span class="text-xs text-slate-400">{{ $permissions->count() }} menu(s)</span>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($permissions as $permission)
                                @php
                                    $menuItem = collect($menuItems)->firstWhere('slug', $permission->slug);
                                @endphp
                                <label class="flex items-start gap-3 rounded-xl border border-slate-200 px-3 py-3 text-sm transition hover:border-brand-200 dark:border-slate-800 dark:hover:border-brand-800">
                                    <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" data-modal-field="permission_ids" class="mt-1">
                                    <span>
                                        <span class="font-medium">{{ $permission->name }}</span>
                                        @if ($menuItem)
                                            <span class="mt-0.5 block text-xs text-slate-400">Sidebar → {{ $group }} → {{ $menuItem['name'] }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                <button type="button" class="btn-secondary" data-form-modal-dismiss>Cancel</button>
                <button type="submit" class="btn-primary">Save Role</button>
            </div>
        </form>
    </x-form-modal>
@endsection
