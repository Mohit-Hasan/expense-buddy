@extends('layouts.app')

@section('title', 'Edit Contact')
@section('heading', 'Edit Contact')
@section('subheading', $contact->name)

@section('actions')
    <a href="{{ route('lending.people.index') }}" class="btn-secondary">Back</a>
    <a href="{{ route('lending.ledger', ['contact_id' => $contact->id]) }}" class="btn-secondary">Ledger</a>
@endsection

@section('content')
    <x-section-nav :items="[
        ['route' => 'lending.overview', 'label' => 'Overview', 'icon' => 'business.safe-box', 'active' => 'lending.overview'],
        ['route' => 'lending.people.index', 'label' => 'Contacts', 'icon' => 'user.group', 'active' => 'lending.people.*'],
        ['route' => 'lending.ledger', 'label' => 'Activity Ledger', 'icon' => 'business.chart-bar', 'active' => 'lending.ledger'],
    ]" />

    <div class="mx-auto max-w-2xl">
        <x-panel title="Update Contact">
            <form method="POST" action="{{ route('lending.people.update', $contact->id) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="label">Type</label>
                    <select name="type" class="input" data-search-select="off" required>
                        <option value="person" @selected(old('type', $contact->type) === 'person')>Person</option>
                        <option value="company" @selected(old('type', $contact->type) === 'company')>Company</option>
                    </select>
                </div>
                <div><label class="label">Name</label><input type="text" name="name" value="{{ old('name', $contact->name) }}" class="input" required></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Email</label><input type="email" name="email" value="{{ old('email', $contact->email) }}" class="input"></div>
                    <div><label class="label">Phone</label><input type="text" name="phone" value="{{ old('phone', $contact->phone) }}" class="input"></div>
                </div>
                <div><label class="label">Organization</label><input type="text" name="company" value="{{ old('company', $contact->company) }}" class="input"></div>
                <div><label class="label">Address</label><textarea name="address" rows="2" class="input">{{ old('address', $contact->address) }}</textarea></div>
                <div>
                    <label class="label">Status</label>
                    <select name="status" class="input" data-search-select="off" required>
                        <option value="active" @selected(old('status', $contact->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $contact->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary">Update</button>
                    <a href="{{ route('lending.people.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </x-panel>
    </div>
@endsection
