import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

export default async function globalSetup(): Promise<void> {
    const envTesting = path.join(root, '.env.testing');
    const envFile = path.join(root, '.env');

    if (fs.existsSync(envTesting)) {
        fs.copyFileSync(envTesting, envFile);
    }

    fs.mkdirSync(path.join(root, 'database'), { recursive: true });
    const testingDb = path.join(root, 'database/testing.sqlite');
    if (fs.existsSync(testingDb)) {
        fs.unlinkSync(testingDb);
    }
    fs.closeSync(fs.openSync(testingDb, 'w'));

    execSync('php artisan expensebuddy:prepare-e2e --demo', {
        cwd: root,
        stdio: 'inherit',
        env: {
            ...process.env,
            APP_ENV: 'testing',
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: testingDb,
        },
    });
}
