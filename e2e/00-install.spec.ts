import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { test, expect } from '@playwright/test';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const logoPath = path.join(root, 'e2e/fixtures/logo.png');

const installAdmin = {
    name: 'E2E Install Admin',
    email: 'e2e-install@test.local',
    password: 'install-pass-123',
};

test.describe.configure({ mode: 'serial' });

test.beforeAll(() => {
    const testingDb = path.join(root, 'database/testing.sqlite');
    if (fs.existsSync(testingDb)) {
        fs.unlinkSync(testingDb);
        fs.closeSync(fs.openSync(testingDb, 'w'));
    }

    execSync('php artisan expensebuddy:prepare-e2e --uninstalled', {
        cwd: root,
        stdio: 'inherit',
        env: {
            ...process.env,
            APP_ENV: 'testing',
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: testingDb,
        },
    });
});

test('fresh install wizard completes successfully', async ({ page, baseURL }) => {
    test.setTimeout(180_000);
    await page.goto('/install/');
    await expect(page.getByRole('heading', { name: 'Server requirements' })).toBeVisible();
    await page.getByRole('link', { name: 'Continue to database' }).click();

    await page.locator('#db_driver').selectOption('sqlite');
    await page.locator('#app_url').fill(baseURL ?? 'http://127.0.0.1:8765');
    await page.locator('#app_env').selectOption('local');
    await page.getByRole('button', { name: 'Test connection & continue' }).click();

    await expect(page.getByRole('heading', { name: 'Application setup' })).toBeVisible();

    await page.locator('#system_name').fill('ExpenseBuddy E2E');
    await page.locator('#system_logo').setInputFiles(logoPath);
    await page.locator('#admin_name').fill(installAdmin.name);
    await page.locator('#admin_email').fill(installAdmin.email);
    await page.locator('#admin_password').fill(installAdmin.password);
    await page.locator('#admin_password_confirmation').fill(installAdmin.password);
    await page.locator('#currency_name').fill('US Dollar');
    await page.locator('#currency_code').fill('USD');
    await page.locator('#currency_symbol').fill('$');
    await page.getByRole('button', { name: 'Run installation' }).click();

    await expect(page.getByRole('heading', { name: 'Installation complete' })).toBeVisible({ timeout: 180_000 });
    await page.getByRole('link', { name: 'Open ExpenseBuddy login' }).click();

    await page.locator('#email').fill(installAdmin.email);
    await page.locator('#password').fill(installAdmin.password);
    await page.getByRole('button', { name: 'Sign In' }).click();

    await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 15_000 });
    await expect(page.getByRole('heading', { level: 1 }).first()).toBeVisible();
});
