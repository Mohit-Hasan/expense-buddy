import { test, expect } from '@playwright/test';
import { admin, loginAsAdmin, logoutViaProfileMenu } from './helpers';

test('admin can login and logout', async ({ page }) => {
    await loginAsAdmin(page);
    await expect(page.getByText('Test Admin').first()).toBeVisible();

    await logoutViaProfileMenu(page);
    await expect(page).toHaveURL(/\/login$/);
});

test('invalid login shows an error', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#email').fill(admin.email);
    await page.locator('#password').fill('not-the-right-password');
    await page.getByRole('button', { name: 'Sign In' }).click();
    await expect(page.locator('text=These credentials do not match our records').or(page.locator('.text-rose-800'))).toBeVisible();
});
