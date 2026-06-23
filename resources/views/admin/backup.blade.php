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
                            <select name="backup_frequency" class="input" data-search-select="off" data-backup-frequency required>
                                <option value="daily" @selected(old('backup_frequency', $settings->backup_frequency) === 'daily')>Daily</option>
                                <option value="weekly" @selected(old('backup_frequency', $settings->backup_frequency) === 'weekly')>Weekly</option>
                                <option value="monthly" @selected(old('backup_frequency', $settings->backup_frequency) === 'monthly')>Monthly</option>
                                <option value="custom" @selected(old('backup_frequency', $settings->backup_frequency) === 'custom')>Custom interval (days)</option>
                            </select>
                        </div>
                        <div data-backup-day-field>
                            <label class="label">Schedule day</label>
                            <input type="number" name="backup_day" min="0" max="365" value="{{ old('backup_day', $settings->backup_day) }}" class="input" required>
                            <p class="mt-1 text-xs text-slate-500" data-backup-day-help>Weekly: 0=Sun … 6=Sat. Monthly: day 1–28. Custom: interval in days. Daily ignores this field.</p>
                        </div>
                    </div>

                    <div>
                        <label class="label">Backup email</label>
                        <input type="email" name="backup_email" value="{{ old('backup_email', $settings->backup_email) }}" placeholder="admin@example.com" class="input">
                    </div>

                    <button type="submit" class="btn-primary">Save Backup Schedule</button>
                </form>

                @if ($backupSupported)
                    <form method="POST" action="{{ route('admin.backup.run') }}" class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                        @csrf
                        <p class="mb-3 text-sm text-slate-500">Send a backup email now using the address above. This updates “Last successful backup” when SMTP is configured.</p>
                        <button type="submit" class="btn-secondary">Send Backup Email Now</button>
                    </form>
                @endif
            </x-panel>

            <x-panel title="Scheduler Status">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">Last scheduler check</dt>
                        <dd class="font-medium">{{ $settings->backup_last_run_at?->format('M j, Y g:i A') ?? 'Never' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Last successful email backup</dt>
                        <dd class="font-medium">{{ $settings->backup_last_success_at?->format('M j, Y g:i A') ?? 'Never' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Active database</dt>
                        <dd class="font-medium">{{ $databaseIdentity }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Backup support</dt>
                        <dd class="font-medium">{{ $backupSupported ? $driverLabel.' ready' : 'Unsupported driver' }}</dd>
                    </div>
                </dl>
                <p class="mt-4 text-xs text-slate-500">Cron must run <code class="rounded bg-slate-100 px-1 py-0.5 dark:bg-slate-800">php artisan schedule:run</code> every minute from this project folder using the same <code class="rounded bg-slate-100 px-1 py-0.5 dark:bg-slate-800">.env</code> as this page (<strong>{{ $databaseIdentity }}</strong>). Use <code class="rounded bg-slate-100 px-1 py-0.5 dark:bg-slate-800">http://localhost:8000</code> for local MySQL — port <code class="rounded bg-slate-100 px-1 py-0.5 dark:bg-slate-800">8765</code> is the E2E SQLite test server.</p>
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
        const backupFrequencySelect = document.querySelector('[data-backup-frequency]');
        const backupDayField = document.querySelector('[data-backup-day-field]');

        const syncBackupDayField = () => {
            if (!backupFrequencySelect || !backupDayField) {
                return;
            }

            const isDaily = backupFrequencySelect.value === 'daily';
            backupDayField.classList.toggle('hidden', isDaily);
        };

        backupFrequencySelect?.addEventListener('change', syncBackupDayField);
        syncBackupDayField();

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
