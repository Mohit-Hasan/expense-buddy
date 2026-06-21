@extends('layouts.app')

@section('title', 'Users')
@section('heading', 'User Management')
@section('subheading', 'Create and manage system users')

@section('actions')
    <x-form-modal-trigger
        :config="[
            'title' => 'Create User',
            'action' => route('admin.users.store'),
            'method' => 'POST',
            'fields' => [
                'name' => '',
                'email' => '',
                'password' => '',
                'role_id' => (string) ($roles->first()?->id ?? ''),
                'status' => 'active',
            ],
            'requiredFields' => ['name', 'email', 'password', 'role_id', 'status'],
        ]"
        class="btn-primary"
    >
        <x-ming-icon name="system.add" class="h-4 w-4" />
        Add User
    </x-form-modal-trigger>
@endsection

@section('content')
    @include('admin.partials.nav')

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Total Users" :value="(string) $stats['total']" color="brand" />
        <x-stat-card label="Active" :value="(string) $stats['active']" color="emerald" />
        <x-stat-card label="Inactive" :value="(string) $stats['inactive']" color="rose" />
    </div>

    <x-panel class="mt-6" title="User List" :subtitle="$users->count().' shown'">
        <form method="GET" class="mb-5 grid gap-3 sm:grid-cols-4">
            <select name="role_id" class="input">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected((string) ($filters['role_id'] ?? '') === (string) $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
            <select name="status" class="input">
                <option value="">All statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
            <div class="flex gap-2 sm:col-span-2">
                <button type="submit" class="btn-primary">Filter</button>
                <a href="{{ route('admin.users') }}" class="btn-secondary">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto -mx-5 px-5">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800">
                        <th class="th">Name</th>
                        <th class="th">Email</th>
                        <th class="th">Role</th>
                        <th class="th">Status</th>
                        <th class="th text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/30 {{ $user->status === 'inactive' ? 'opacity-70' : '' }}">
                            <td class="td font-medium">{{ $user->name }}</td>
                            <td class="td">{{ $user->email }}</td>
                            <td class="td">{{ $user->role?->name ?? '—' }}</td>
                            <td class="td">
                                <span class="badge {{ $user->status === 'active' ? 'badge-income' : 'badge-expense' }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td class="td">
                                <div class="flex justify-end gap-2">
                                    <x-form-modal-trigger
                                        :config="[
                                            'title' => 'Edit User',
                                            'action' => route('admin.users.update', $user->id),
                                            'method' => 'PUT',
                                            'fields' => [
                                                'name' => $user->name,
                                                'email' => $user->email,
                                                'password' => '',
                                                'role_id' => (string) $user->role_id,
                                                'status' => $user->status,
                                            ],
                                            'requiredFields' => ['name', 'email', 'role_id', 'status'],
                                        ]"
                                        class="btn-secondary !px-3 !py-1.5 text-xs"
                                    >
                                        Edit
                                    </x-form-modal-trigger>
                                    @if ($user->id !== auth()->id())
                                        <button type="button"
                                                data-confirm="Delete user &quot;{{ $user->name }}&quot;? This cannot be undone."
                                                form="delete-user-{{ $user->id }}"
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
                                No users match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>

    @foreach ($users as $user)
        @if ($user->id !== auth()->id())
            <form id="delete-user-{{ $user->id }}" method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach

    <x-form-modal>
        <form id="form-modal-form" method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Full name</label>
                <input type="text" name="name" data-modal-field="name" data-modal-required="name" class="input" placeholder="Full name" required>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Email</label>
                <input type="email" name="email" data-modal-field="email" data-modal-required="email" class="input" placeholder="Email address" required>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Password</label>
                <input type="password" name="password" data-modal-field="password" data-modal-required="password" class="input" placeholder="Minimum 8 characters" minlength="8">
                <p class="mt-1 text-xs text-slate-400">Leave blank when editing to keep the current password.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Role</label>
                    <select name="role_id" data-modal-field="role_id" data-modal-required="role_id" class="input" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
                    <select name="status" data-modal-field="status" data-modal-required="status" class="input" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="btn-secondary" data-form-modal-dismiss>Cancel</button>
                <button type="submit" class="btn-primary">Save User</button>
            </div>
        </form>
    </x-form-modal>
@endsection
