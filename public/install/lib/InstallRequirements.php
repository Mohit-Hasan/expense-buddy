<?php

declare(strict_types=1);

final class InstallRequirements
{
    public function __construct(
        private readonly string $basePath,
    ) {}

    /**
     * @return list<array{label: string, ok: bool, hint: string|null}>
     */
    public function checks(): array
    {
        return [
            [
                'label' => 'PHP 8.3 or higher',
                'ok' => version_compare(PHP_VERSION, '8.3.0', '>='),
                'hint' => 'Current: '.PHP_VERSION,
            ],
            [
                'label' => 'PDO extension',
                'ok' => extension_loaded('pdo'),
                'hint' => 'Enable the PDO PHP extension',
            ],
            [
                'label' => 'OpenSSL extension',
                'ok' => extension_loaded('openssl'),
                'hint' => 'Enable the OpenSSL PHP extension',
            ],
            [
                'label' => 'Mbstring extension',
                'ok' => extension_loaded('mbstring'),
                'hint' => 'Enable the Mbstring PHP extension',
            ],
            [
                'label' => 'Tokenizer extension',
                'ok' => extension_loaded('tokenizer'),
                'hint' => 'Enable the Tokenizer PHP extension',
            ],
            [
                'label' => 'XML extension',
                'ok' => extension_loaded('xml'),
                'hint' => 'Enable the XML PHP extension',
            ],
            [
                'label' => 'Fileinfo extension',
                'ok' => extension_loaded('fileinfo'),
                'hint' => 'Enable the Fileinfo PHP extension',
            ],
            [
                'label' => 'GD extension (logo upload)',
                'ok' => extension_loaded('gd'),
                'hint' => 'Enable the GD PHP extension',
            ],
            [
                'label' => 'Vendor folder included',
                'ok' => is_file($this->basePath.'/vendor/autoload.php'),
                'hint' => 'Upload the full package including /vendor',
            ],
            [
                'label' => 'storage/ is writable',
                'ok' => is_writable($this->basePath.'/storage'),
                'hint' => 'chmod -R 775 storage',
            ],
            [
                'label' => 'bootstrap/cache/ is writable',
                'ok' => is_writable($this->basePath.'/bootstrap/cache'),
                'hint' => 'chmod -R 775 bootstrap/cache',
            ],
            [
                'label' => 'Project root is writable (.env)',
                'ok' => is_writable($this->basePath),
                'hint' => 'The app folder must allow creating .env',
            ],
        ];
    }

    public function passed(): bool
    {
        foreach ($this->checks() as $check) {
            if (! $check['ok']) {
                return false;
            }
        }

        return true;
    }
}
