import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const testingDb = path.join(root, 'database/testing.sqlite');

function syncTestingEnv(): void {
    const envTesting = path.join(root, '.env.testing');
    const envFile = path.join(root, '.env');

    if (fs.existsSync(envTesting)) {
        fs.copyFileSync(envTesting, envFile);
    }
}

function resetTestingDatabase(): void {
    fs.mkdirSync(path.join(root, 'database'), { recursive: true });

    if (fs.existsSync(testingDb)) {
        fs.unlinkSync(testingDb);
    }

    fs.closeSync(fs.openSync(testingDb, 'w'));
}

function prepareDatabase(mode: '--demo' | '--uninstalled'): void {
    execSync(`php artisan expensebuddy:prepare-e2e ${mode}`, {
        cwd: root,
        stdio: 'inherit',
        env: {
            ...process.env,
            APP_ENV: 'testing',
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: 'database/testing.sqlite',
        },
    });
}

export default async function globalSetup(): Promise<void> {
    if (process.env.PLAYWRIGHT_SKIP_GLOBAL_SETUP === '1') {
        return;
    }

    syncTestingEnv();

    const installMode = process.env.PLAYWRIGHT_INCLUDE_INSTALL === '1';
    const lock = path.join(root, 'storage/installed');

    if (installMode && fs.existsSync(lock)) {
        fs.unlinkSync(lock);
    }

    resetTestingDatabase();
    prepareDatabase(installMode ? '--uninstalled' : '--demo');
}
