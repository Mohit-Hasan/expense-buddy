import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8765';
const port = new URL(baseURL).port || '8765';
const includeInstall = process.env.PLAYWRIGHT_INCLUDE_INSTALL === '1';
const htmlReportDir = process.env.PLAYWRIGHT_HTML_REPORT ?? 'playwright-report';

export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: [['list'], ['html', { open: 'never', outputFolder: htmlReportDir }]],
    testIgnore: includeInstall ? ['**/0[1-9]-*.spec.ts'] : '**/00-install.spec.ts',
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    globalSetup: process.env.PLAYWRIGHT_SKIP_GLOBAL_SETUP === '1' ? undefined : './e2e/global-setup.ts',
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: {
        command: `php artisan serve --port=${port} --no-reload`,
        url: `${baseURL}/up`,
        reuseExistingServer: !process.env.CI,
        stdout: 'pipe',
        stderr: 'pipe',
    },
});
