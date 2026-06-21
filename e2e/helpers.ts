import { expect, Page } from '@playwright/test';

export const admin = {
    email: 'admin@expensebuddy.test',
    password: 'password',
};

export async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.locator('#email').fill(admin.email);
    await page.locator('#password').fill(admin.password);
    await page.getByRole('button', { name: 'Sign In' }).click();
    await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 15_000 });
    await expect(page.getByRole('heading', { level: 1 }).first()).toBeVisible();
}

/** Page @section('actions') buttons are rendered twice (header + mobile main). Scope to header. */
export async function clickPageAction(page: Page, name: string): Promise<void> {
    await page.getByRole('banner').getByRole('button', { name }).click();
}

export const authenticatedRoutes = [
    '/',
    '/transactions',
    '/transactions/create',
    '/transfers/create',
    '/accounts',
    '/categories',
    '/lending',
    '/lending/ledger',
    '/lending/people',
    '/lending/people/create',
    '/reports/income-vs-expense',
    '/reports/categorized',
    '/reports/detailed',
    '/admin/settings',
    '/admin/currencies',
    '/admin/users',
    '/admin/roles',
];
