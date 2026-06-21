@extends('layouts.app')

@section('title', 'Add Contact')
@section('heading', 'Add Contact')
@section('subheading', 'Register a person or company')

@section('actions')
    <a href="{{ route('lending.people.index') }}" class="btn-secondary">Back</a>
@endsection

@section('content')
    <x-section-nav :items="[
        ['route' => 'lending.overview', 'label' => 'Overview', 'icon' => 'business.safe-box', 'active' => 'lending.overview'],
        ['route' => 'lending.people.index', 'label' => 'Contacts', 'icon' => 'user.group', 'active' => 'lending.people.*'],
        ['route' => 'lending.ledger', 'label' => 'Activity Ledger', 'icon' => 'business.chart-bar', 'active' => 'lending.ledger'],
    ]" />

    <div class="mx-auto max-w-2xl">
        <x-panel title="Contact Details">
            <form method="POST" action="{{ route('lending.people.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="label">Type</label>
                    <select name="type" class="input" data-search-select="off" required>
                        <option value="person" @selected(old('type') === 'person')>Person — individual</option>
                        <option value="company" @selected(old('type') === 'company')>Company — business entity</option>
                    </select>
                </div>
                <div>
                    <label class="label">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="input" required>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="input"></div>
                    <div><label class="label">Phone</label><input type="text" name="phone" value="{{ old('phone') }}" class="input"></div>
                </div>
                <div><label class="label">Organization</label><input type="text" name="company" value="{{ old('company') }}" placeholder="Optional" class="input"></div>
                <div><label class="label">Address</label><textarea name="address" rows="2" class="input">{{ old('address') }}</textarea></div>
                <div>
                    <label class="label">Status</label>
                    <select name="status" class="input" data-search-select="off" required>
                        <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary">Save</button>
                    <a href="{{ route('lending.people.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </x-panel>
    </div>
@endsection
