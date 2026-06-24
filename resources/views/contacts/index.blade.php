@extends('layouts.app')

@section('title', 'Contacts')
@section('heading', 'Contacts')
@section('subheading', 'People and companies — link to income, expenses, or lending')

@section('actions')
    <a href="{{ route('contacts.create') }}" class="btn-primary">
        <x-ming-icon name="user.user-add" class="h-4 w-4" />
        Add Contact
    </a>
@endsection

@section('content')
    @php use App\Support\MoneyFormatter; @endphp

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ([['title' => 'People', 'subtitle' => 'Individuals', 'items' => $people], ['title' => 'Companies', 'subtitle' => 'Business entities', 'items' => $companies]] as $group)
            <x-panel :title="$group['title']" :subtitle="$group['subtitle']">
                <div class="space-y-3">
                    @forelse ($group['items'] as $contact)
                        <div class="person-card !cursor-default">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-semibold truncate">{{ $contact->name }}</div>
                                        <div class="mt-0.5 text-xs text-slate-500">{{ $contact->email ?? $contact->phone ?? 'No contact info' }}</div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <div class="text-xs text-slate-500">Lending balance</div>
                                        <div class="amount amount-neutral">{{ MoneyFormatter::format((string) $contact->current_balance, $baseCurrency) }}</div>
                                        <span class="badge {{ $contact->status === 'active' ? 'badge-income' : 'badge-expense' }} mt-1">{{ ucfirst($contact->status) }}</span>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="{{ route('contacts.show', $contact->id) }}" class="btn-secondary !px-3 !py-1.5 text-xs">Activity</a>
                                    <a href="{{ route('contacts.edit', $contact->id) }}" class="btn-secondary !px-3 !py-1.5 text-xs">Edit</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No {{ strtolower($group['title']) }} yet.</p>
                    @endforelse
                </div>
            </x-panel>
        @endforeach
    </div>
@endsection
