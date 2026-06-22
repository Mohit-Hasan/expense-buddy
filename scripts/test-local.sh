#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "==> Running PHPUnit"
php artisan test

echo "==> Preparing Playwright environment"
cp .env.testing .env
mkdir -p database
rm -f database/testing.sqlite storage/installed
php artisan expensebuddy:prepare-e2e --demo

echo "==> Building frontend assets"
npm run build

echo "==> Running Playwright app suite"
PLAYWRIGHT_SKIP_GLOBAL_SETUP=1 npx playwright test

if [[ "${RUN_INSTALL_E2E:-0}" == "1" ]]; then
  echo "==> Running Playwright install wizard suite"
  PLAYWRIGHT_INCLUDE_INSTALL=1 PLAYWRIGHT_SKIP_GLOBAL_SETUP=1 npx playwright test e2e/00-install.spec.ts
fi

echo "All local tests passed."
