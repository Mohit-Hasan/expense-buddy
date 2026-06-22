import { expect, Page } from '@playwright/test';

export const admin = {
    email: 'admin@expensebuddy.test',
    password: 'password',
};

export type LedgerTransactionType =
    | 'income'
    | 'expense'
    | 'lending_out'
    | 'lending_in'
    | 'lending_repay_in'
    | 'lending_repay_out';

export function uniqueRunId(): string {
    return `${Date.now()}-${Math.floor(Math.random() * 1000)}`;
}

export function parseMoneyAmount(text: string): number {
    const normalized = text.replace(/,/g, '');
    const match = normalized.match(/-?\d+(?:\.\d+)?/);

    if (!match) {
        throw new Error(`Could not parse money amount from: ${text}`);
    }

    return parseFloat(match[0]);
}

export async function selectOptionContaining(page: Page, selector: string, text: string): Promise<void> {
    const select = page.locator(selector);
    const option = select.locator('option').filter({ hasText: text }).first();
    const value = await option.getAttribute('value');

    if (!value) {
        throw new Error(`No <option> containing "${text}" found for selector ${selector}`);
    }

    await select.selectOption(value);
}

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

export async function logoutViaProfileMenu(page: Page): Promise<void> {
    await page.locator('#profile-menu-toggle').click();
    await expect(page.locator('#profile-menu-panel')).toBeVisible();
    await page.locator('#profile-menu-panel').getByRole('menuitem', { name: 'Logout' }).click();
}

export async function createLedgerAccount(
    page: Page,
    title: string,
    initialBalance: string,
    accountNumber?: string,
): Promise<void> {
    await page.goto('/accounts');

    const createForm = page.locator('form').filter({
        has: page.getByRole('button', { name: 'Create Account' }),
    });

    await createForm.getByPlaceholder('Account title').fill(title);

    if (accountNumber) {
        await createForm.getByPlaceholder('Account number (optional)').fill(accountNumber);
    }

    await createForm.locator('select[name="currency_id"]').selectOption({ index: 0 });
    await createForm.locator('input[name="initial_balance"]').fill(initialBalance);
    await createForm.getByRole('button', { name: 'Create Account' }).click();

    await expect(page.getByText('Account created successfully')).toBeVisible();
}

export async function readAccountBalance(page: Page, accountTitle: string): Promise<number> {
    await page.goto('/accounts');

    const card = page.locator('.card').filter({
        has: page.getByRole('heading', { name: accountTitle, level: 3 }),
    });

    const amount = card.locator('.amount-lg').first();
    await expect(amount).toBeVisible();

    return parseMoneyAmount(await amount.innerText());
}

export async function createCategory(
    page: Page,
    name: string,
    type: 'income' | 'expense',
): Promise<void> {
    await page.goto('/categories');
    await clickPageAction(page, 'Add Category');
    await expect(page.locator('#form-modal')).toBeVisible();
    await page.locator('#form-modal input[name="name"]').fill(name);
    await page.locator('#form-modal select[name="type"]').selectOption(type);
    await page.getByRole('button', { name: 'Save Category' }).click();
    await expect(page.getByText('Category created successfully')).toBeVisible();
}

export async function createLendingContact(page: Page, name: string, email?: string): Promise<void> {
    await page.goto('/lending/people/create');
    await page.locator('input[name="name"]').fill(name);

    if (email) {
        await page.locator('input[name="email"]').fill(email);
    }

    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('Contact added successfully')).toBeVisible();
}

export async function recordTransaction(
    page: Page,
    options: {
        type: LedgerTransactionType;
        accountTitle: string;
        amount: string;
        contactName?: string;
        categoryName?: string;
        reference?: string;
    },
): Promise<void> {
    await page.goto('/transactions/create');
    await page.locator('select[name="type"]').selectOption(options.type);
    await selectOptionContaining(page, 'select[name="account_id"]', options.accountTitle);

    if (options.type.startsWith('lending')) {
        await expect(page.locator('#category-field-wrap')).toBeHidden();
        await expect(options.contactName).toBeTruthy();
        await selectOptionContaining(page, 'select[name="contact_id"]', options.contactName!);
    } else {
        if (options.categoryName) {
            await selectOptionContaining(page, 'select[name="category_id"]', options.categoryName);
        } else {
            await page.locator('select[name="category_id"]').selectOption({ index: 1 });
        }

        await page.locator('select[name="payment_method_id"]').selectOption({ index: 1 });
    }

    await page.locator('input[name="amount"]').fill(options.amount);
    await page.locator('input[name="transaction_date"]').fill(new Date().toISOString().slice(0, 10));
    await page.locator('input[name="reference"]').fill(options.reference ?? `PW-${uniqueRunId()}`);
    await page.getByRole('button', { name: 'Save Transaction' }).click();
    await expect(page.getByText('Transaction recorded successfully')).toBeVisible();
}

export async function recordTransfer(
    page: Page,
    options: {
        fromAccountTitle: string;
        toAccountTitle: string;
        amount: string;
    },
): Promise<void> {
    await page.goto('/transfers/create');
    await selectOptionContaining(page, 'select[name="source_account_id"]', options.fromAccountTitle);
    await selectOptionContaining(page, 'select[name="destination_account_id"]', options.toAccountTitle);
    await page.locator('select[name="payment_method_id"]').selectOption({ index: 1 });
    await page.locator('input[name="amount"]').fill(options.amount);
    await page.locator('input[name="transaction_date"]').fill(new Date().toISOString().slice(0, 10));
    await page.getByRole('button', { name: 'Execute Transfer' }).click();
    await expect(page.getByText('Transfer completed successfully')).toBeVisible();
}

export async function readContactOutstanding(page: Page, contactName: string): Promise<number> {
    await page.goto('/lending/ledger');
    await selectOptionContaining(page, 'select[name="contact_id"]', contactName);
    await page.getByRole('button', { name: 'Apply' }).click();

    await expect(page.getByText(contactName).first()).toBeVisible();

    const stat = page.locator('.stat-card').filter({ hasText: 'Outstanding Balance' });
    await expect(stat).toBeVisible();

    return parseMoneyAmount(await stat.locator('.amount-lg').innerText());
}

export const authenticatedRoutes = [
    '/',
    '/transactions',
    '/transactions/create',
    '/transfers/create',
    '/accounts',
    '/categories',
    '/payment-methods',
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
