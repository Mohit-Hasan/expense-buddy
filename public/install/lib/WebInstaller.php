<?php

declare(strict_types=1);

use App\Services\InstallService;
use App\Support\AppInstall;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;

require_once __DIR__.'/EnvWriter.php';
require_once __DIR__.'/InstallRequirements.php';

final class WebInstaller
{
    public function __construct(
        private readonly string $basePath,
    ) {}

    public function requirements(): InstallRequirements
    {
        return new InstallRequirements($this->basePath);
    }

    public function isInstalled(): bool
    {
        return is_file($this->basePath.'/storage/installed');
    }

    /**
     * @param array<string, mixed> $database
     */
    public function testDatabaseConnection(array $database): ?string
    {
        $driver = $database['driver'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                $path = $this->sqlitePath($database['database'] ?? 'database/database.sqlite');

                if (! is_dir(dirname($path))) {
                    mkdir(dirname($path), 0755, true);
                }

                if (! is_file($path)) {
                    touch($path);
                }

                new PDO('sqlite:'.$path);

                return null;
            }

            $host = (string) ($database['host'] ?? '127.0.0.1');
            $port = (string) ($database['port'] ?? '3306');
            $name = (string) ($database['database'] ?? '');
            $user = (string) ($database['username'] ?? '');
            $pass = (string) ($database['password'] ?? '');

            if ($name === '' || $user === '') {
                return 'Database name and username are required.';
            }

            $pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
            $pdo->query('SELECT 1');

            return null;
        } catch (Throwable $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    public function run(array $payload, string $logoTmpPath, string $logoOriginalName): array
    {
        $logs = [];

        if (($payload['confirm_reinstall'] ?? '') !== '1' && $this->isInstalled()) {
            throw new RuntimeException('Application is already installed. Enable fresh reinstall to continue.');
        }

        AppInstall::clearLock();

        $logs[] = 'Creating .env file…';
        $this->writeEnvironment($payload);

        $logs[] = 'Linking public storage…';
        $this->ensureStorageLink();

        $logs[] = 'Preparing database (fresh install)…';
        $app = $this->bootstrapLaravel();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $logs[] = trim(Artisan::output()) ?: 'Migrations completed.';

        $logs[] = 'Creating administrator and settings…';
        $uploadedLogo = new UploadedFile(
            $logoTmpPath,
            $logoOriginalName,
            mime_content_type($logoTmpPath) ?: 'image/png',
            null,
            true,
        );

        $app->make(InstallService::class)->install([
            'admin_name' => (string) $payload['admin_name'],
            'admin_email' => (string) $payload['admin_email'],
            'admin_password' => (string) $payload['admin_password'],
            'system_name' => (string) $payload['system_name'],
            'currency_name' => (string) $payload['currency_name'],
            'currency_code' => (string) $payload['currency_code'],
            'currency_symbol' => (string) $payload['currency_symbol'],
            'allow_negative_balances' => ($payload['allow_negative_balances'] ?? '') === '1',
        ], $uploadedLogo, true);

        if (($payload['demo_data'] ?? '') === '1') {
            $logs[] = 'Loading demo accounts, categories, and payment methods…';
            (new DemoDataSeeder())->run();
        }

        AppInstall::markInstalled();
        $logs[] = 'Installation completed successfully.';

        return $logs;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeEnvironment(array $payload): void
    {
        $envPath = $this->basePath.'/.env';
        $writer = new EnvWriter($envPath);
        $writer->loadFromExample($this->basePath.'/.env.example');

        $driver = ($payload['db_driver'] ?? 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';
        $appUrl = rtrim((string) ($payload['app_url'] ?? ''), '/');

        $writer->set('APP_NAME', (string) ($payload['system_name'] ?? 'ExpenseBuddy'));
        $writer->set('APP_BRAND_NAME', (string) ($payload['system_name'] ?? 'ExpenseBuddy'));
        $writer->set('APP_ENV', ($payload['app_env'] ?? 'production') === 'local' ? 'local' : 'production');
        $writer->set('APP_DEBUG', ($payload['app_env'] ?? 'production') === 'local' ? 'true' : 'false');
        $writer->set('APP_URL', $appUrl !== '' ? $appUrl : 'http://localhost');
        $writer->set('APP_KEY', 'base64:'.base64_encode(random_bytes(32)));
        $writer->set('DB_CONNECTION', $driver);
        $writer->set('LEDGER_ALLOW_NEGATIVE_BALANCES', ($payload['allow_negative_balances'] ?? '') === '1' ? 'true' : 'false');

        if ($driver === 'sqlite') {
            $writer->set('DB_DATABASE', $this->sqlitePath((string) ($payload['sqlite_path'] ?? 'database/database.sqlite')));
            $writer->set('DB_HOST', '');
            $writer->set('DB_PORT', '');
            $writer->set('DB_USERNAME', '');
            $writer->set('DB_PASSWORD', '');
        } else {
            $writer->set('DB_HOST', (string) ($payload['db_host'] ?? '127.0.0.1'));
            $writer->set('DB_PORT', (string) ($payload['db_port'] ?? '3306'));
            $writer->set('DB_DATABASE', (string) ($payload['db_database'] ?? ''));
            $writer->set('DB_USERNAME', (string) ($payload['db_username'] ?? ''));
            $writer->set('DB_PASSWORD', (string) ($payload['db_password'] ?? ''));
        }

        $writer->save();
    }

    private function sqlitePath(string $relativeOrAbsolute): string
    {
        if ($relativeOrAbsolute === '' || $relativeOrAbsolute === ':memory:') {
            $relativeOrAbsolute = 'database/database.sqlite';
        }

        if (str_starts_with($relativeOrAbsolute, '/')) {
            return $relativeOrAbsolute;
        }

        return $this->basePath.'/'.ltrim($relativeOrAbsolute, '/');
    }

    private function ensureStorageLink(): void
    {
        $link = $this->basePath.'/public/storage';
        $target = $this->basePath.'/storage/app/public';

        if (is_link($link) || is_dir($link)) {
            return;
        }

        if (! is_dir($target)) {
            mkdir($target, 0755, true);
        }

        if (function_exists('symlink')) {
            @symlink($target, $link);

            return;
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('mklink /J '.escapeshellarg($link).' '.escapeshellarg($target));
        }
    }

    private function bootstrapLaravel(): \Illuminate\Foundation\Application
    {
        require_once $this->basePath.'/vendor/autoload.php';

        $app = require $this->basePath.'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
