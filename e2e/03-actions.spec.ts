import { test, expect } from '@playwright/test';
import { clickPageAction, loginAsAdmin } from './helpers';

test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
});

test('admin can create an account from the accounts page', async ({ page }) => {
    const accountNumber = `PW-${Date.now()}`;

    await page.goto('/accounts');

    const createForm = page.locator('form').filter({
        has: page.getByRole('button', { name: 'Create Account' }),
    });

    await createForm.getByPlaceholder('Account title').fill('Playwright Cash');
    await createForm.getByPlaceholder('Account number (optional)').fill(accountNumber);
    await createForm.locator('select[name="currency_id"]').selectOption({ index: 0 });
    await createForm.locator('input[name="initial_balance"]').fill('500');
    await createForm.getByRole('button', { name: 'Create Account' }).click();

    await expect(page.getByText('Account created successfully')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Playwright Cash' }).first()).toBeVisible();
});

test('admin can create a category and income transaction', async ({ page }) => {
    await page.goto('/categories');
    await clickPageAction(page, 'Add Category');
    await expect(page.locator('#form-modal')).toBeVisible();
    await page.locator('#form-modal input[name="name"]').fill(`PW Income ${Date.now()}`);
    await page.locator('#form-modal select[name="type"]').selectOption('income');
    await page.getByRole('button', { name: 'Save Category' }).click();

    await page.goto('/transactions/create');
    await page.locator('select[name="type"]').selectOption('income');
    await page.locator('select[name="account_id"]').selectOption({ index: 1 });
    await page.locator('select[name="category_id"]').selectOption({ index: 1 });
    await page.locator('select[name="payment_method_id"]').selectOption({ index: 1 });
    await page.locator('input[name="amount"]').fill('120');
    await page.locator('input[name="transaction_date"]').fill(new Date().toISOString().slice(0, 10));
    await page.locator('input[name="reference"]').fill(`PW-E2E-${Date.now()}`);
    await page.getByRole('button', { name: 'Save Transaction' }).click();

    await expect(page.getByText('Transaction recorded successfully')).toBeVisible();
});

test('admin can create a lending contact', async ({ page }) => {
    await page.goto('/contacts/create');
    await page.locator('input[name="name"]').fill(`Playwright Contact ${Date.now()}`);
    await page.locator('input[name="email"]').fill(`playwright-${Date.now()}@test.local`);
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText(/Playwright Contact/)).toBeVisible();
});
