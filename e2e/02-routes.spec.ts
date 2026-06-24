import { test, expect } from '@playwright/test';
import { authenticatedRoutes, loginAsAdmin } from './helpers';

test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
});

test('admin can visit every main feature page', async ({ page }) => {
    for (const route of authenticatedRoutes) {
        await page.goto(route);
        await expect(page.locator('body')).not.toContainText('404');
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.getByRole('heading', { level: 1 }).first()).toBeVisible();
    }
});

test('redirect routes resolve', async ({ page }) => {
    await page.goto('/contacts');
    await expect(page).toHaveURL(/\/contacts$/);
    await expect(page.getByRole('heading', { level: 1, name: 'Contacts' })).toBeVisible();

    await page.goto('/lending/people');
    await expect(page).toHaveURL(/\/contacts$/);

    await page.goto('/reports/contact-ledger');
    await expect(page).toHaveURL(/\/lending\/ledger$/);
});

test('pwa manifest is available', async ({ request }) => {
    const response = await request.get('/manifest.webmanifest');
    expect(response.ok()).toBeTruthy();

    const manifest = await response.json();
    expect(manifest.name).toBeTruthy();
    expect(manifest.icons?.length).toBeGreaterThan(0);
});
