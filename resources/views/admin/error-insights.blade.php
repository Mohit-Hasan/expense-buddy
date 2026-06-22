@extends('layouts.app')

@section('title', 'Error Insights')
@section('heading', 'Administration')
@section('subheading', 'Failed route hits ranked by frequency')

@section('content')
    @php use Illuminate\Support\Str; @endphp

    @include('admin.partials.nav')

    <x-panel title="Error Route Report" subtitle="Tracks 403, 404, 419, 500, and 503 responses when enabled in General Settings">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">{{ number_format($errorHits->total()) }} tracked {{ Str::plural('path', $errorHits->total()) }}</p>
            <form method="POST" action="{{ route('admin.error-insights.clear') }}">
                @csrf
                <button type="submit" class="btn-secondary text-sm">Clear all stats</button>
            </form>
        </div>

        <div class="overflow-x-auto -mx-5 px-5">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800">
                        <th class="th">Path</th>
                        <th class="th">Status</th>
                        <th class="th">Hits</th>
                        <th class="th">Last seen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($errorHits as $hit)
                        <tr>
                            <td class="td font-mono text-xs">{{ $hit->path }}</td>
                            <td class="td">{{ $hit->status_code }}</td>
                            <td class="td font-semibold">{{ number_format($hit->hit_count) }}</td>
                            <td class="td text-slate-500">{{ \Illuminate\Support\Carbon::parse($hit->last_hit_at)->format('M j, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">No tracked errors yet. They will appear here after visitors hit missing or denied routes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($errorHits->hasPages())
            <div class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-800">
                {{ $errorHits->links() }}
            </div>
        @endif
    </x-panel>
@endsection
