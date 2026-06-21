<?php

declare(strict_types=1);

final class EnvWriter
{
    /** @var array<string, string> */
    private array $values = [];

    public function __construct(
        private readonly string $envPath,
    ) {}

    public function loadFromExample(string $examplePath): void
    {
        if (! is_file($examplePath)) {
            throw new RuntimeException('.env.example file was not found.');
        }

        $lines = file($examplePath, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            throw new RuntimeException('Unable to read .env.example.');
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $this->values[trim($key)] = $this->stripQuotes(trim($value));
        }
    }

    public function set(string $key, string $value): void
    {
        $this->values[$key] = $value;
    }

    public function save(): void
    {
        $order = [
            'APP_NAME', 'APP_BRAND_NAME', 'APP_BRAND_TAGLINE', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL', 'APP_TIMEZONE',
            'LOG_CHANNEL', 'LOG_LEVEL',
            'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
            'SESSION_DRIVER', 'SESSION_LIFETIME',
            'CACHE_STORE', 'FILESYSTEM_DISK', 'QUEUE_CONNECTION',
            'LEDGER_ALLOW_NEGATIVE_BALANCES',
        ];

        $written = [];

        foreach ($order as $key) {
            if (array_key_exists($key, $this->values)) {
                $written[$key] = $this->values[$key];
            }
        }

        foreach ($this->values as $key => $value) {
            if (! array_key_exists($key, $written)) {
                $written[$key] = $value;
            }
        }

        $content = '';

        foreach ($written as $key => $value) {
            $content .= $key.'='.$this->quote($value).PHP_EOL;
        }

        if (file_put_contents($this->envPath, $content) === false) {
            throw new RuntimeException('Unable to write .env file. Check folder permissions.');
        }
    }

    private function stripQuotes(string $value): string
    {
        if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    private function quote(string $value): string
    {
        if ($value === '' || preg_match('/\s|#|=|,/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
