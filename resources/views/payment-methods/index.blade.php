@extends('layouts.app')

@section('title', 'Payment Methods')
@section('heading', 'Payment Methods')
@section('subheading', 'Manage how money moves — archive instead of delete to preserve history')

@section('actions')
    <x-form-modal-trigger
        :config="[
            'title' => 'Create Payment Method',
            'action' => route('payment-methods.store'),
            'method' => 'POST',
            'fields' => ['name' => '', 'status' => 'active'],
        ]"
        class="btn-primary"
    >
        <x-ming-icon name="system.add" class="h-4 w-4" />
        Add Method
    </x-form-modal-trigger>
@endsection

@section('content')
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Total Methods" :value="(string) $stats['total']" color="brand" />
        <x-stat-card label="Active" :value="(string) $stats['active']" color="emerald" />
        <x-stat-card label="Archived" :value="(string) $stats['archived']" color="rose" />
    </div>

    <x-panel class="mt-6" title="Payment Method List" :subtitle="$paymentMethods->count().' shown'">
        <form method="GET" class="mb-5 grid gap-3 sm:grid-cols-4">
            <select name="status" class="input sm:col-span-2">
                <option value="">All statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Archived</option>
            </select>
            <div class="flex gap-2 sm:col-span-2">
                <button type="submit" class="btn-primary">Filter</button>
                <a href="{{ route('payment-methods.index') }}" class="btn-secondary">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto -mx-5 px-5">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800">
                        <th class="th">Name</th>
                        <th class="th">Status</th>
                        <th class="th text-right">Transactions</th>
                        <th class="th text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($paymentMethods as $method)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/30 {{ $method->status === 'inactive' ? 'opacity-70' : '' }}">
                            <td class="td font-medium">{{ $method->name }}</td>
                            <td class="td">
                                <span class="badge {{ $method->status === 'active' ? 'badge-income' : 'badge-expense' }}">
                                    {{ $method->status === 'active' ? 'Active' : 'Archived' }}
                                </span>
                            </td>
                            <td class="td text-right font-mono text-sm">{{ $method->transactions_count }}</td>
                            <td class="td">
                                <div class="flex justify-end gap-2">
                                    @if ($method->status === 'active')
                                        <x-form-modal-trigger
                                            :config="[
                                                'title' => 'Edit Payment Method',
                                                'action' => route('payment-methods.update', $method->id),
                                                'method' => 'PUT',
                                                'fields' => [
                                                    'name' => $method->name,
                                                    'status' => $method->status,
                                                ],
                                            ]"
                                            class="btn-secondary !px-3 !py-1.5 text-xs"
                                        >
                                            Edit
                                        </x-form-modal-trigger>
                                        <button type="button"
                                                data-confirm="Archive &quot;{{ $method->name }}&quot;? Existing transactions will keep this method, but it will be hidden from new entries."
                                                form="archive-payment-method-{{ $method->id }}"
                                                class="text-xs font-medium text-amber-600 hover:underline">
                                            Archive
                                        </button>
                                    @else
                                        <button type="button"
                                                data-confirm="Restore &quot;{{ $method->name }}&quot; for use in new transactions?"
                                                form="restore-payment-method-{{ $method->id }}"
                                                class="btn-secondary !px-3 !py-1.5 text-xs">
                                            Restore
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">
                                No payment methods match your filters.
                                <x-form-modal-trigger
                                    :config="[
                                        'title' => 'Create Payment Method',
                                        'action' => route('payment-methods.store'),
                                        'method' => 'POST',
                                        'fields' => ['name' => '', 'status' => 'active'],
                                    ]"
                                    class="ml-1 font-medium text-brand-600 hover:underline"
                                >
                                    Create one
                                </x-form-modal-trigger>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>

    @foreach ($paymentMethods as $method)
        @if ($method->status === 'active')
            <form id="archive-payment-method-{{ $method->id }}" method="POST" action="{{ route('payment-methods.archive', $method->id) }}" class="hidden">
                @csrf
            </form>
        @else
            <form id="restore-payment-method-{{ $method->id }}" method="POST" action="{{ route('payment-methods.restore', $method->id) }}" class="hidden">
                @csrf
            </form>
        @endif
    @endforeach

    <x-form-modal>
        <form id="form-modal-form" method="POST" action="{{ route('payment-methods.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="status" value="active" data-modal-field="status">

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Name</label>
                <input type="text" name="name" data-modal-field="name" class="input" placeholder="e.g. Bank Transfer" required>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="btn-secondary" data-form-modal-dismiss>Cancel</button>
                <button type="submit" class="btn-primary">Save Method</button>
            </div>
        </form>
    </x-form-modal>
@endsection
