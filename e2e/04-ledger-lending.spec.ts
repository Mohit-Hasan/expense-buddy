import { test, expect } from '@playwright/test';
import {
    createCategory,
    createLedgerAccount,
    createLendingContact,
    loginAsAdmin,
    readAccountBalance,
    readContactOutstanding,
    recordTransaction,
    recordTransfer,
    selectOptionContaining,
    uniqueRunId,
} from './helpers';

test.describe.serial('ledger, lending, and balance cross-checks', () => {
    const runId = uniqueRunId();
    const mainAccount = `PW Main ${runId}`;
    const savingsAccount = `PW Savings ${runId}`;
    const contactName = `PW Contact ${runId}`;
    const incomeCategory = `PW Income ${runId}`;
    const expenseCategory = `PW Expense ${runId}`;
    const referencePrefix = `PW-LEDGER-${runId}`;

    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page);
    });

    test('creates account, categories, and lending contact', async ({ page }) => {
        await createLedgerAccount(page, mainAccount, '1000', `PW-MAIN-${runId}`);
        await createLedgerAccount(page, savingsAccount, '0', `PW-SAVE-${runId}`);
        await createCategory(page, incomeCategory, 'income');
        await createCategory(page, expenseCategory, 'expense');
        await createLendingContact(page, contactName, `pw-contact-${runId}@test.local`);

        expect(await readAccountBalance(page, mainAccount)).toBe(1000);
        expect(await readAccountBalance(page, savingsAccount)).toBe(0);
        expect(await readContactOutstanding(page, contactName)).toBe(0);
    });

    test('records every transaction type and verifies balances', async ({ page }) => {
        const steps = [
            {
                type: 'income' as const,
                amount: '200',
                ref: `${referencePrefix}-income`,
                accountDelta: 200,
                contactDelta: 0,
            },
            {
                type: 'expense' as const,
                amount: '150',
                ref: `${referencePrefix}-expense`,
                accountDelta: -150,
                contactDelta: 0,
                categoryName: expenseCategory,
            },
            {
                type: 'lending_out' as const,
                amount: '300',
                ref: `${referencePrefix}-loan-out`,
                accountDelta: -300,
                contactDelta: 300,
            },
            {
                type: 'lending_repay_in' as const,
                amount: '100',
                ref: `${referencePrefix}-repay-in`,
                accountDelta: 100,
                contactDelta: -100,
            },
            {
                type: 'lending_in' as const,
                amount: '80',
                ref: `${referencePrefix}-loan-in`,
                accountDelta: 80,
                contactDelta: -80,
            },
            {
                type: 'lending_repay_out' as const,
                amount: '20',
                ref: `${referencePrefix}-repay-out`,
                accountDelta: -20,
                contactDelta: 20,
            },
        ];

        let expectedAccount = 1000;
        let expectedContact = 0;

        for (const step of steps) {
            await recordTransaction(page, {
                type: step.type,
                accountTitle: mainAccount,
                amount: step.amount,
                contactName: step.type.startsWith('lending') ? contactName : undefined,
                categoryName: step.type === 'income' ? incomeCategory : step.categoryName,
                reference: step.ref,
            });

            expectedAccount += step.accountDelta;
            expectedContact += step.contactDelta;

            expect(await readAccountBalance(page, mainAccount)).toBe(expectedAccount);
            expect(await readContactOutstanding(page, contactName)).toBe(expectedContact);
        }

        // Expected: 1000 + 200 - 150 - 300 + 100 + 80 - 20 = 910
        expect(expectedAccount).toBe(910);
        // Expected contact: 300 - 100 - 80 + 20 = 140
        expect(expectedContact).toBe(140);
    });

    test('transfer between accounts does not change lending balance', async ({ page }) => {
        const mainBefore = await readAccountBalance(page, mainAccount);
        const savingsBefore = await readAccountBalance(page, savingsAccount);
        const contactBefore = await readContactOutstanding(page, contactName);

        await recordTransfer(page, {
            fromAccountTitle: mainAccount,
            toAccountTitle: savingsAccount,
            amount: '100',
        });

        expect(await readAccountBalance(page, mainAccount)).toBe(mainBefore - 100);
        expect(await readAccountBalance(page, savingsAccount)).toBe(savingsBefore + 100);
        expect(await readContactOutstanding(page, contactName)).toBe(contactBefore);
    });

    test('ledger and lending activity lists recorded entries', async ({ page }) => {
        await page.goto('/transactions');
        await selectOptionContaining(page, 'select[name="contact_id"]', contactName);
        await page.getByRole('button', { name: 'Apply' }).click();

        const txnTable = page.locator('table tbody');
        await expect(txnTable.getByText('Loan Out').first()).toBeVisible();
        await expect(txnTable.getByText('Loan In').first()).toBeVisible();
        await expect(txnTable.getByText('Repayment In').first()).toBeVisible();
        await expect(txnTable.getByText('Repayment Out').first()).toBeVisible();
        await expect(txnTable.getByText(contactName).first()).toBeVisible();

        await page.goto('/lending/ledger');
        await selectOptionContaining(page, 'select[name="contact_id"]', contactName);
        await page.getByRole('button', { name: 'View Activity' }).click();

        const ledgerTable = page.locator('table tbody');
        await expect(ledgerTable.getByText('Loan Out').first()).toBeVisible();
        await expect(ledgerTable.getByText('Loan In').first()).toBeVisible();
        await expect(ledgerTable.getByText('Repayment In').first()).toBeVisible();
        await expect(ledgerTable.getByText('Repayment Out').first()).toBeVisible();
        await expect(ledgerTable.getByText('300.00').first()).toBeVisible();
        await expect(
            page.locator('.stat-card').filter({ hasText: 'Lending Outstanding' }).getByText('140.00'),
        ).toBeVisible();
    });
});
