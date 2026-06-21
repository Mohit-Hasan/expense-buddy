<?php

declare(strict_types=1);

session_start();

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

if (preg_match('#/install$#', $requestPath)) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: /install/'.($query !== '' ? '?'.$query : ''), true, 301);
    exit;
}

const INSTALL_BASE = '/install/';

$basePath = dirname(__DIR__, 2);
require_once __DIR__.'/lib/WebInstaller.php';

$installer = new WebInstaller($basePath);
$step = max(1, min(5, (int) ($_GET['step'] ?? $_POST['step'] ?? 1)));
$errors = [];
$logs = [];
$installed = $installer->isInstalled();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        $_SESSION['install'] = array_merge($_SESSION['install'] ?? [], [
            'db_driver' => $_POST['db_driver'] ?? 'mysql',
            'db_host' => trim($_POST['db_host'] ?? '127.0.0.1'),
            'db_port' => trim($_POST['db_port'] ?? '3306'),
            'db_database' => trim($_POST['db_database'] ?? ''),
            'db_username' => trim($_POST['db_username'] ?? ''),
            'db_password' => (string) ($_POST['db_password'] ?? ''),
            'sqlite_path' => trim($_POST['sqlite_path'] ?? 'database/database.sqlite'),
            'app_url' => trim($_POST['app_url'] ?? ''),
            'app_env' => $_POST['app_env'] ?? 'production',
        ]);

        $error = $installer->testDatabaseConnection([
            'driver' => $_SESSION['install']['db_driver'],
            'host' => $_SESSION['install']['db_host'],
            'port' => $_SESSION['install']['db_port'],
            'database' => $_SESSION['install']['db_driver'] === 'sqlite'
                ? $_SESSION['install']['sqlite_path']
                : $_SESSION['install']['db_database'],
            'username' => $_SESSION['install']['db_username'],
            'password' => $_SESSION['install']['db_password'],
        ]);

        if ($error !== null) {
            $errors[] = 'Database connection failed: '.$error;
        } else {
            header('Location: '.INSTALL_BASE.'?step=3');

            exit;
        }
    }

    if ($step === 3) {
        if (empty($_FILES['system_logo']['tmp_name'])) {
            $errors[] = 'Logo is required. It will be used as favicon and mobile app icon.';
        }

        if (trim($_POST['admin_password'] ?? '') !== trim($_POST['admin_password_confirmation'] ?? '')) {
            $errors[] = 'Administrator passwords do not match.';
        }

        if ($errors === []) {
            $_SESSION['install'] = array_merge($_SESSION['install'] ?? [], [
                'system_name' => trim($_POST['system_name'] ?? 'ExpenseBuddy'),
                'admin_name' => trim($_POST['admin_name'] ?? ''),
                'admin_email' => trim($_POST['admin_email'] ?? ''),
                'admin_password' => (string) ($_POST['admin_password'] ?? ''),
                'currency_name' => trim($_POST['currency_name'] ?? 'US Dollar'),
                'currency_code' => strtoupper(trim($_POST['currency_code'] ?? 'USD')),
                'currency_symbol' => trim($_POST['currency_symbol'] ?? '$'),
                'allow_negative_balances' => isset($_POST['allow_negative_balances']) ? '1' : '0',
                'demo_data' => isset($_POST['demo_data']) ? '1' : '0',
                'confirm_reinstall' => isset($_POST['confirm_reinstall']) ? '1' : '0',
            ]);

            $_SESSION['install_logo'] = [
                'tmp_name' => $_FILES['system_logo']['tmp_name'],
                'name' => $_FILES['system_logo']['name'],
            ];

            copy($_FILES['system_logo']['tmp_name'], sys_get_temp_dir().'/expensebuddy-logo-'.session_id());
            $_SESSION['install_logo']['stored'] = sys_get_temp_dir().'/expensebuddy-logo-'.session_id();

            header('Location: '.INSTALL_BASE.'?step=4');

            exit;
        }
    }

    if ($step === 4) {
        $data = $_SESSION['install'] ?? [];
        $logo = $_SESSION['install_logo'] ?? null;

        if ($data === [] || $logo === null || ! is_file($logo['stored'])) {
            $errors[] = 'Installation session expired. Please start again.';
            $step = 3;
        } else {
            try {
                $logs = $installer->run($data, $logo['stored'], $logo['name']);
                $_SESSION['install_complete'] = true;
                $_SESSION['install_admin_email'] = $data['admin_email'] ?? '';
                unset($_SESSION['install'], $_SESSION['install_logo']);
                header('Location: '.INSTALL_BASE.'?step=5');

                exit;
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }
    }
}

$data = $_SESSION['install'] ?? [];
$requirements = $installer->requirements();
$reqPassed = $requirements->passed();
$complete = (bool) ($_SESSION['install_complete'] ?? false);

if ($step === 5 && ! $complete) {
    header('Location: '.INSTALL_BASE.'?step=1');

    exit;
}

$defaultUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    .'://'.($_SERVER['HTTP_HOST'] ?? 'localhost');

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function stepClass(int $current, int $target): string
{
    if ($current === $target) {
        return 'active';
    }

    if ($current > $target) {
        return 'done';
    }

    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExpenseBuddy Installer</title>
    <base href="<?= INSTALL_BASE ?>">
    <link rel="stylesheet" href="<?= INSTALL_BASE ?>assets/installer.css">
</head>
<body>
<div class="wrap">
    <div class="hero">
        <div class="logo-badge">EB</div>
        <h1>ExpenseBuddy Installer</h1>
        <p>Your Personal Finance Companion — web setup wizard</p>
    </div>

    <div class="install-card">
        <div class="steps">
            <div class="step <?= stepClass($step, 1) ?>">1. Requirements</div>
            <div class="step <?= stepClass($step, 2) ?>">2. Database</div>
            <div class="step <?= stepClass($step, 3) ?>">3. Application</div>
            <div class="step <?= stepClass($step, 5) ?>">4. Finish</div>
        </div>

        <div class="content">
            <?php if ($errors !== []): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= h($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($installed && $step < 4): ?>
                <div class="alert alert-warning">
                    ExpenseBuddy is already installed. Continue only if you want a <strong>fresh reinstall</strong> (all data will be erased on step 3).
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <h2>Server requirements</h2>
                <p class="lead">This package includes the <strong>vendor</strong> folder — no Composer is needed on your server. Upload the full ZIP, open this installer URL, and follow the steps.</p>

                <ul class="check-list">
                    <?php foreach ($requirements->checks() as $check): ?>
                        <li class="<?= $check['ok'] ? 'ok' : 'bad' ?>">
                            <div><strong><?= $check['ok'] ? '✓' : '✕' ?></strong></div>
                            <div>
                                <div><?= h($check['label']) ?></div>
                                <?php if (! $check['ok'] && $check['hint']): ?>
                                    <small><?= h($check['hint']) ?></small>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="actions">
                    <span></span>
                    <?php if ($reqPassed): ?>
                        <a class="btn btn-primary" href="<?= INSTALL_BASE ?>?step=2">Continue to database</a>
                    <?php else: ?>
                        <span class="btn btn-secondary" style="opacity:.6;cursor:not-allowed">Fix requirements to continue</span>
                    <?php endif; ?>
                </div>

            <?php elseif ($step === 2): ?>
                <h2>Database &amp; site URL</h2>
                <p class="lead">The installer will create your <code>.env</code> file automatically. Use MySQL/MariaDB on live hosting, or SQLite for local testing.</p>

                <form method="POST" action="<?= INSTALL_BASE ?>">
                    <input type="hidden" name="step" value="2">

                    <div class="field">
                        <label for="db_driver">Database type</label>
                        <select id="db_driver" name="db_driver" onchange="toggleDbFields(this.value)">
                            <option value="mysql" <?= ($data['db_driver'] ?? 'mysql') === 'mysql' ? 'selected' : '' ?>>MySQL / MariaDB (live server)</option>
                            <option value="sqlite" <?= ($data['db_driver'] ?? '') === 'sqlite' ? 'selected' : '' ?>>SQLite (local / simple hosting)</option>
                        </select>
                    </div>

                    <div id="mysql-fields">
                        <div class="grid-2">
                            <div class="field">
                                <label for="db_host">Host</label>
                                <input id="db_host" name="db_host" value="<?= h($data['db_host'] ?? '127.0.0.1') ?>">
                            </div>
                            <div class="field">
                                <label for="db_port">Port</label>
                                <input id="db_port" name="db_port" value="<?= h($data['db_port'] ?? '3306') ?>">
                            </div>
                            <div class="field">
                                <label for="db_database">Database name</label>
                                <input id="db_database" name="db_database" value="<?= h($data['db_database'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label for="db_username">Username</label>
                                <input id="db_username" name="db_username" value="<?= h($data['db_username'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label for="db_password">Password</label>
                                <input id="db_password" type="password" name="db_password" value="<?= h($data['db_password'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div id="sqlite-fields" class="hidden">
                        <div class="field">
                            <label for="sqlite_path">SQLite file path</label>
                            <input id="sqlite_path" name="sqlite_path" value="<?= h($data['sqlite_path'] ?? 'database/database.sqlite') ?>">
                            <small>Relative to project root. The installer creates the file if needed.</small>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="app_url">Website URL</label>
                            <input id="app_url" name="app_url" value="<?= h($data['app_url'] ?? $defaultUrl) ?>" required>
                            <small>Example: https://yourdomain.com</small>
                        </div>
                        <div class="field">
                            <label for="app_env">Environment</label>
                            <select id="app_env" name="app_env">
                                <option value="production" <?= ($data['app_env'] ?? 'production') === 'production' ? 'selected' : '' ?>>Live (production)</option>
                                <option value="local" <?= ($data['app_env'] ?? '') === 'local' ? 'selected' : '' ?>>Local (debug enabled)</option>
                            </select>
                        </div>
                    </div>

                    <div class="actions">
                        <a class="btn btn-secondary" href="<?= INSTALL_BASE ?>?step=1">Back</a>
                        <button type="submit" class="btn btn-primary">Test connection &amp; continue</button>
                    </div>
                </form>

            <?php elseif ($step === 3): ?>
                <h2>Application setup</h2>
                <p class="lead">Every install runs <strong>migrate:fresh</strong> for a clean database. Choose demo data only if you want sample accounts and categories to explore the app.</p>

                <form method="POST" action="<?= INSTALL_BASE ?>" enctype="multipart/form-data">
                    <input type="hidden" name="step" value="3">

                    <div class="grid-2">
                        <div class="field">
                            <label for="system_name">App name</label>
                            <input id="system_name" name="system_name" value="<?= h($data['system_name'] ?? 'ExpenseBuddy') ?>" required>
                        </div>
                        <div class="field">
                            <label for="system_logo">Logo &amp; favicon *</label>
                            <input id="system_logo" type="file" name="system_logo" accept="image/*" required>
                            <small>Square PNG/JPG recommended — used in sidebar, browser tab, and mobile install icon.</small>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="admin_name">Administrator name</label>
                            <input id="admin_name" name="admin_name" value="<?= h($data['admin_name'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="admin_email">Administrator email</label>
                            <input id="admin_email" type="email" name="admin_email" value="<?= h($data['admin_email'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="admin_password">Password</label>
                            <input id="admin_password" type="password" name="admin_password" required>
                        </div>
                        <div class="field">
                            <label for="admin_password_confirmation">Confirm password</label>
                            <input id="admin_password_confirmation" type="password" name="admin_password_confirmation" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="currency_name">Base currency name</label>
                            <input id="currency_name" name="currency_name" value="<?= h($data['currency_name'] ?? 'US Dollar') ?>" required>
                        </div>
                        <div class="field">
                            <label for="currency_code">Currency code</label>
                            <input id="currency_code" name="currency_code" maxlength="3" value="<?= h($data['currency_code'] ?? 'USD') ?>" required>
                        </div>
                        <div class="field">
                            <label for="currency_symbol">Currency symbol</label>
                            <input id="currency_symbol" name="currency_symbol" value="<?= h($data['currency_symbol'] ?? '$') ?>" required>
                        </div>
                    </div>

                    <div class="option-card">
                        <label>
                            <input type="checkbox" name="demo_data" value="1" <?= ($data['demo_data'] ?? '') === '1' ? 'checked' : '' ?>>
                            <span>
                                <strong>Include demo data</strong>
                                <span>Sample bank accounts, categories, payment methods, and contacts. Your administrator login from above is still used.</span>
                            </span>
                        </label>
                    </div>

                    <div class="option-card">
                        <label>
                            <input type="checkbox" name="allow_negative_balances" value="1" <?= ($data['allow_negative_balances'] ?? '') === '1' ? 'checked' : '' ?>>
                            <span>
                                <strong>Allow negative balances</strong>
                                <span>When unchecked, expenses and transfers cannot exceed available account balance.</span>
                            </span>
                        </label>
                    </div>

                    <?php if ($installed): ?>
                        <div class="option-card">
                            <label>
                                <input type="checkbox" name="confirm_reinstall" value="1" required>
                                <span>
                                    <strong>Fresh reinstall — erase all existing data</strong>
                                    <span>Required because the app is already installed. This cannot be undone.</span>
                                </span>
                            </label>
                        </div>
                    <?php endif; ?>

                    <div class="actions">
                        <a class="btn btn-secondary" href="<?= INSTALL_BASE ?>?step=2">Back</a>
                        <button type="submit" class="btn btn-primary">Run installation</button>
                    </div>
                </form>

            <?php elseif ($step === 4): ?>
                <h2>Installing…</h2>
                <p class="lead">Please wait while ExpenseBuddy configures your application.</p>

                <?php if ($logs !== []): ?>
                    <div class="log-box"><?= h(implode("\n", $logs)) ?></div>
                <?php elseif ($errors !== []): ?>
                    <div class="actions">
                        <a class="btn btn-secondary" href="<?= INSTALL_BASE ?>?step=3">Back to application setup</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?= INSTALL_BASE ?>" id="run-form">
                        <input type="hidden" name="step" value="4">
                        <div class="actions">
                            <a class="btn btn-secondary" href="<?= INSTALL_BASE ?>?step=3">Back</a>
                            <button type="submit" class="btn btn-primary">Start installation</button>
                        </div>
                    </form>
                    <script>document.getElementById('run-form')?.requestSubmit();</script>
                <?php endif; ?>

            <?php elseif ($step === 5): ?>
                <h2>Installation complete</h2>
                <p class="lead">ExpenseBuddy is ready. Storage link and environment file are already configured.</p>

                <div class="alert alert-success">
                    Sign in with <strong><?= h($_SESSION['install_admin_email'] ?? 'your admin email') ?></strong> and the password you chose during setup.
                </div>

                <ul class="check-list">
                    <li class="ok"><div><strong>✓</strong></div><div>.env file created</div></li>
                    <li class="ok"><div><strong>✓</strong></div><div>Database migrated (fresh)</div></li>
                    <li class="ok"><div><strong>✓</strong></div><div>public/storage linked</div></li>
                    <li class="ok"><div><strong>✓</strong></div><div>Administrator account created</div></li>
                </ul>

                <div class="alert alert-warning">
                    For security, delete the <code>/public/install</code> folder from your server after confirming login works.
                </div>

                <div class="actions">
                    <span></span>
                    <a class="btn btn-primary" href="/login">Open ExpenseBuddy login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleDbFields(driver) {
    document.getElementById('mysql-fields').classList.toggle('hidden', driver === 'sqlite');
    document.getElementById('sqlite-fields').classList.toggle('hidden', driver !== 'sqlite');
    document.getElementById('db_database').required = driver !== 'sqlite';
    document.getElementById('db_username').required = driver !== 'sqlite';
}
toggleDbFields(document.getElementById('db_driver')?.value || 'mysql');
</script>
</body>
</html>
