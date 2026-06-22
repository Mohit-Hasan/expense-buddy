<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use App\Services\MailConfigService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupService
{
    public function __construct(
        private readonly MailConfigService $mailConfigService,
        private readonly SystemSettingService $systemSettingService,
    ) {
    }

    public function driver(): string
    {
        return (string) config('database.default');
    }

    public function isMysql(): bool
    {
        return $this->driver() === 'mysql'
            && is_string(config('database.connections.mysql.database'))
            && config('database.connections.mysql.database') !== '';
    }

    public function isSqlite(): bool
    {
        return $this->driver() === 'sqlite';
    }

    public function isSupported(): bool
    {
        return $this->isMysql() || $this->isSqlite();
    }

    public function driverLabel(): string
    {
        return match (true) {
            $this->isMysql() => 'MySQL',
            $this->isSqlite() => 'SQLite',
            default => strtoupper($this->driver()),
        };
    }

    public function generateSql(): string
    {
        if ($this->isMysql()) {
            return $this->generateMysqlSql();
        }

        if ($this->isSqlite()) {
            return $this->generateSqliteSql();
        }

        throw new RuntimeException('Database backup is only supported for MySQL and SQLite connections.');
    }

    public function gzipSql(string $sql): string
    {
        $compressed = gzencode($sql, 9);

        if ($compressed === false) {
            throw new RuntimeException('Failed to compress database backup.');
        }

        return $compressed;
    }

    public function downloadResponse(): StreamedResponse
    {
        $filename = 'ledger_backup_'.now()->format('Y_m_d_His').'.sql.gz';

        return response()->streamDownload(function (): void {
            echo $this->gzipSql($this->generateSql());
        }, $filename, [
            'Content-Type' => 'application/gzip',
        ]);
    }

    public function emailBackup(SystemSetting $settings): void
    {
        if ($settings->backup_email === null || $settings->backup_email === '') {
            throw new RuntimeException('Backup email address is not configured.');
        }

        $this->mailConfigService->applyFromSettings($settings);

        $filename = 'ledger_backup_'.now()->format('Y_m_d_His').'.sql.gz';
        $payload = $this->gzipSql($this->generateSql());

        Mail::raw(
            'Automated database backup from '.($settings->system_name ?? 'ExpenseBuddy').' at '.now()->toDateTimeString().'.',
            function ($message) use ($settings, $filename, $payload): void {
                $message->to($settings->backup_email)
                    ->subject('Database backup — '.now()->format('M j, Y H:i'))
                    ->attachData($payload, $filename, ['mime' => 'application/gzip']);
            }
        );
    }

    public function shouldRunScheduledBackup(SystemSetting $settings): bool
    {
        if (! $settings->backup_enabled) {
            return false;
        }

        if ($settings->backup_email === null || $settings->backup_email === '') {
            return false;
        }

        if (! $this->isSupported()) {
            return false;
        }

        $lastSuccess = $settings->backup_last_success_at;

        return match ($settings->backup_frequency) {
            'monthly' => $this->isMonthlyDue($settings, $lastSuccess),
            'custom' => $this->isCustomIntervalDue($settings, $lastSuccess),
            default => $this->isWeeklyDue($settings, $lastSuccess),
        };
    }

    public function runScheduledBackup(): bool
    {
        $settings = $this->systemSettingService->get();
        $settings->update(['backup_last_run_at' => now()]);

        if (! $this->shouldRunScheduledBackup($settings)) {
            return false;
        }

        try {
            $this->emailBackup($settings->fresh());
            $settings->update(['backup_last_success_at' => now()]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Scheduled database backup failed: '.$exception->getMessage());

            return false;
        }
    }

    private function generateMysqlSql(): string
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_'.$database;
        $output = "-- ExpenseBuddy Database Backup\n";
        $output .= '-- Generated at: '.now()->toDateTimeString()."\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->{$tableKey};
            $createRow = DB::selectOne('SHOW CREATE TABLE `'.$tableName.'`');
            $createSql = $createRow->{'Create Table'} ?? '';

            $output .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $output .= $createSql.";\n\n";

            foreach (DB::table($tableName)->cursor() as $row) {
                $columns = array_keys((array) $row);
                $values = array_map(function (mixed $value): string {
                    if ($value === null) {
                        return 'NULL';
                    }

                    return "'".str_replace("'", "''", (string) $value)."'";
                }, array_values((array) $row));

                $output .= 'INSERT INTO `'.$tableName.'` (`'.implode('`, `', $columns).'`) VALUES ('.implode(', ', $values).");\n";
            }

            $output .= "\n";
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $output;
    }

    private function generateSqliteSql(): string
    {
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $output = "-- ExpenseBuddy Database Backup\n";
        $output .= '-- Generated at: '.now()->toDateTimeString()."\n\n";
        $output .= "PRAGMA foreign_keys = OFF;\n\n";

        foreach ($tables as $table) {
            $tableName = (string) $table->name;
            $createRow = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$tableName]);
            $createSql = (string) ($createRow->sql ?? '');

            $output .= "DROP TABLE IF EXISTS \"{$tableName}\";\n";
            $output .= $createSql.";\n\n";

            foreach (DB::table($tableName)->cursor() as $row) {
                $columns = array_keys((array) $row);
                $values = array_map(function (mixed $value): string {
                    if ($value === null) {
                        return 'NULL';
                    }

                    if (is_int($value) || is_float($value)) {
                        return (string) $value;
                    }

                    return "'".str_replace("'", "''", (string) $value)."'";
                }, array_values((array) $row));

                $quotedColumns = implode(', ', array_map(static fn (string $column): string => '"'.$column.'"', $columns));
                $output .= 'INSERT INTO "'.$tableName.'" ('.$quotedColumns.') VALUES ('.implode(', ', $values).");\n";
            }

            $output .= "\n";
        }

        $output .= "PRAGMA foreign_keys = ON;\n";

        return $output;
    }

    private function isWeeklyDue(SystemSetting $settings, ?\Illuminate\Support\Carbon $lastSuccess): bool
    {
        if ($lastSuccess === null) {
            return (int) now()->dayOfWeek === (int) $settings->backup_day;
        }

        if ($lastSuccess->diffInDays(now()) < 7) {
            return false;
        }

        return (int) now()->dayOfWeek === (int) $settings->backup_day;
    }

    private function isMonthlyDue(SystemSetting $settings, ?\Illuminate\Support\Carbon $lastSuccess): bool
    {
        $targetDay = min(max((int) $settings->backup_day, 1), 28);

        if ((int) now()->day !== $targetDay) {
            return false;
        }

        if ($lastSuccess === null) {
            return true;
        }

        return ! $lastSuccess->isSameMonth(now());
    }

    private function isCustomIntervalDue(SystemSetting $settings, ?\Illuminate\Support\Carbon $lastSuccess): bool
    {
        $intervalDays = min(max((int) $settings->backup_day, 1), 365);

        if ($lastSuccess === null) {
            return true;
        }

        return $lastSuccess->diffInDays(now()) >= $intervalDays;
    }
}
