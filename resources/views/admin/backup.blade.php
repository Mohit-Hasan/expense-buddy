@extends('layouts.app')

@section('title', 'Database Backup')
@section('heading', 'Database Backup')
@section('subheading', 'Schedule automated email backups or download a snapshot now')

@section('content')
    @include('admin.partials.nav')

    <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-1 dark:border-slate-800">
        <button type="button" data-backup-tab="schedule" class="backup-tab-btn rounded-t-lg px-4 py-2 text-sm font-medium transition bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
            Schedule
        </button>
        <button type="button" data-backup-tab="download" class="backup-tab-btn rounded-t-lg px-4 py-2 text-sm font-medium transition text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
            Download
        </button>
    </div>

    <div data-backup-panel="schedule">
        <div class="grid gap-6 xl:grid-cols-3">
            <x-panel class="xl:col-span-2" title="Automated Backup" subtitle="Requires SMTP email settings under Administration → Settings">
                <form method="POST" action="{{ route('admin.backup.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <label class="flex items-center gap-3 text-sm">
                        <input type="checkbox" name="backup_enabled" value="1" @checked(old('backup_enabled', $settings->backup_enabled)) class="rounded border-slate-300 text-brand-600">
                        <span>Enable scheduled email backups</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Frequency</label>
                            <select name="backup_frequency" class="input" data-search-select="off" required>
                                <option value="weekly" @selected(old('backup_frequency', $settings->backup_frequency) === 'weekly')>Weekly</option>
                                <option value="monthly" @selected(old('backup_frequency', $settings->backup_frequency) === 'monthly')>Monthly</option>
                                <option value="custom" @selected(old('backup_frequency', $settings->backup_frequency) === 'custom')>Custom interval (days)</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Schedule day</label>
                            <input type="number" name="backup_day" min="0" max="365" value="{{ old('backup_day', $settings->backup_day) }}" class="input" required>
                            <p class="mt-1 text-xs text-slate-500">Weekly: 0=Sun … 6=Sat. Monthly: day 1–28. Custom: interval in days.</p>
                        </div>
                    </div>

                    <div>
                        <label class="label">Backup email</label>
                        <input type="email" name="backup_email" value="{{ old('backup_email', $settings->backup_email) }}" placeholder="admin@example.com" class="input">
                    </div>

                    <button type="submit" class="btn-primary">Save Backup Schedule</button>
                </form>
            </x-panel>

            <x-panel title="Scheduler Status">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">Last scheduler check</dt>
                        <dd class="font-medium">{{ $settings->backup_last_run_at?->format('M j, Y g:i A') ?? 'Never' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Last successful backup</dt>
                        <dd class="font-medium">{{ $settings->backup_last_success_at?->format('M j, Y g:i A') ?? 'Never' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Database</dt>
                        <dd class="font-medium">{{ $backupSupported ? $driverLabel.' ready' : 'Unsupported driver' }}</dd>
                    </div>
                </dl>
                <p class="mt-4 text-xs text-slate-500">Cron should run <code class="rounded bg-slate-100 px-1 py-0.5 dark:bg-slate-800">php artisan schedule:run</code> every minute. Backups email as gzip-compressed SQL when due.</p>
            </x-panel>
        </div>
    </div>

    <div data-backup-panel="download" class="hidden">
        <x-panel title="Download Now" subtitle="Immediate gzip-compressed SQL export">
            @if ($backupSupported)
                <a href="{{ route('admin.backup.download') }}" class="btn-primary">Download Backup (.sql.gz)</a>
                <p class="mt-3 text-sm text-slate-500">Exports the full {{ $driverLabel }} database without leaving this page until you click download.</p>
            @else
                <p class="text-sm text-slate-500">Direct download is only available for MySQL and SQLite database connections.</p>
            @endif
        </x-panel>
    </div>

    <script>
        document.querySelectorAll('[data-backup-tab]').forEach((button) => {
            button.addEventListener('click', () => {
                const tab = button.dataset.backupTab;

                document.querySelectorAll('[data-backup-tab]').forEach((el) => {
                    const active = el.dataset.backupTab === tab;
                    el.classList.toggle('bg-brand-50', active);
                    el.classList.toggle('text-brand-700', active);
                    el.classList.toggle('dark:bg-brand-900/30', active);
                    el.classList.toggle('dark:text-brand-300', active);
                    el.classList.toggle('text-slate-600', !active);
                    el.classList.toggle('hover:bg-slate-100', !active);
                    el.classList.toggle('dark:text-slate-400', !active);
                    el.classList.toggle('dark:hover:bg-slate-800', !active);
                });

                document.querySelectorAll('[data-backup-panel]').forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.backupPanel !== tab);
                });
            });
        });
    </script>
@endsection
