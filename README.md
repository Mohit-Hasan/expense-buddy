# Prompt: Build a Financial Ledger Engine (Income & Expense Manager)
# Target Stack: Laravel 11.x, PHP 8.3+, Tailwind CSS, Livewire or Clean Blade, MySQL

You are an expert enterprise-grade Laravel Software Architect. We are building a streamlined, highly accurate Income and Expense Management system based on the "Cash Lite" footprint, but with a decoupled, robust Service-Repository architectural pattern.

## Core Architectural Guardrails
1. **Strict Typing:** All generated PHP files must declare `strict_types=1`. Use explicit property, parameter, and return types everywhere.
2. **Design Pattern:** Code must follow a strict Controller -> Service -> Repository flow.
   - **Repositories:** Responsible strictly for database querying, data persistence, and fetching via Eloquent models.
   - **Services:** Responsible for business rules, validation logic checks, state transitions, and grouping data adjustments.
   - **Controllers:** Thin entry points that capture `FormRequest` inputs and return JSON or HTML views.
3. **Data Integrity:** Financial fields must never use floating-point types (`float`, `double`). All monetary values must use `decimal(15, 4)` in the database and handled carefully to avoid rounding errors. All multi-account balance mutations (like Transfers) must be wrapped explicitly inside database transactions (`DB::transaction`).
4. **N+1 Prevention:** All relational queries must explicitly state eager loading (`with([...])`) inside the repositories.

---

## 1. Database Schema & Migration Blueprint

Generate the database migrations sequentially with precise foreign key constraints and indexes.

### 1.1 Core Foundations & RBAC
* `roles`: `id`, `name` (string), `slug` (string, unique), `timestamps`
* `permissions`: `id`, `name` (string), `slug` (string, unique), `group` (string), `timestamps`
* `permission_role`: `role_id` (foreign), `permission_id` (foreign)
* `users`: `id`, `name`, `email` (unique), `password`, `role_id` (foreign), `status` (enum: active, inactive), `remember_token`, `timestamps`

### 1.2 Localization & Setup Entities
* `currencies`: `id`, `name` (string), `code` (string, e.g., USD, BDT, EUR), `symbol` (string), `exchange_rate` (decimal 15,4), `is_default` (boolean), `timestamps`
* `transaction_categories`: `id`, `name` (string), `type` (enum: income, expense), `status` (enum: active, inactive), `timestamps`
* `payment_methods`: `id`, `name` (string), `status` (enum: active, inactive), `timestamps`

### 1.3 Accounts & Contacts
* `accounts`: `id`, `account_title` (string), `account_number` (string, nullable), `currency_id` (foreign), `initial_balance` (decimal 15,4), `current_balance` (decimal 15,4), `note` (text, nullable), `timestamps`
* `contacts`: `id`, `type` (enum: customer, vendor), `name` (string), `email` (string, nullable), `phone` (string, nullable), `company` (string, nullable), `address` (text, nullable), `current_balance` (decimal 15,4, default 0), `status` (enum: active, inactive), `timestamps`

### 1.4 The Financial Ledger
* `transactions`:
  * `id` (bigint, pk)
  * `account_id` (foreign, restricted cascade)
  * `type` (enum: income, expense, transfer)
  * `category_id` (foreign, nullable, set null on delete)
  * `payment_method_id` (foreign, restricted cascade)
  * `currency_id` (foreign)
  * `amount` (decimal 15,4) - *Always positive; context determined by type*
  * `rate_at_transaction` (decimal 15,4)
  * `contact_id` (foreign, nullable, tracks Customer for income or Vendor for expense)
  * `transaction_date` (date)
  * `reference` (string, nullable)
  * `description` (text, nullable)
  * `attachment` (string, nullable)
  * `transfer_reference_id` (bigint, self-referential nullable foreign key linking matching debits/credits for transfers)
  * `timestamps`

---

## 2. Directory & Namespace Structure

Ensure files are built exactly into these structural pathways:
```text
app/
├── DTOs/                 # Data Transfer Objects for sanitized business inputs
├── Http/
│   ├── Controllers/      # Slim routing endpoints
│   └── Requests/         # Form validation logic rules
├── Models/               # Pure Eloquent schemas with typed relationship mappings
├── Repositories/
│   ├── Contracts/        # Standardized query interfaces
│   └── Eloquent/         # Concrete Eloquent implementations
└── Services/             # Pure atomic business logic handlers

3. Core Layer Design Patterns
3.1 Base Repository Pattern Example
Every repository must implement an explicit Interface contract.

PHP
namespace App\Repositories\Contracts;

interface AccountRepositoryInterface {
    public function allActive();
    public function findWithLock(int $id);
    public function updateBalance(int $id, float $amount, string $operationType): bool;
}
3.2 Transaction Processing Business Rules (Service Layer)
When creating or modifying financial state records, the TransactionService must implement these calculations:

Income Operations:

Increments target accounts.current_balance.

If a contact_id exists, increments the customer's balance value to reflect lifecycle transaction history.

Expense Operations:

Decrements target accounts.current_balance.

Checks if balance goes below zero (if negative balances are forbidden by config).

If a contact_id exists, increments the vendor's spent total balance tracker.

Transfer Operations:

Executed within a transactional block:

PHP
DB::transaction(function () use ($dto) { ... })
Creates an expense style ledger log entry for the originating account.

Creates an income style ledger log entry for the destination account.

Mutates both corresponding account current balances precisely.

Links both entries via the transfer_reference_id.

4. UI/UX & Module Specifications
Generate standard, responsive Blade + Tailwind layout components. Use an elegant, clean dark/light minimalist dashboard style.

Module 1: Dashboard: Contains statistics widgets (Total Income, Total Expense, Net Balance, Net Cash Assets) calculated using a dedicated service class. Includes an interactive visual tracking line chart for the last 6 months.

Module 2: Transaction Managers: Paginated transactional tables with filtering capabilities for type, category, date limits, and account sources. Inline modal form adjustments for fast transaction entries.

Module 3: Reports Engine: - Income Vs Expense Summary: Displays month-over-month margins.

Categorized Breakdown: Aggregated financial allocations grouped by category types.

Contact Balance Ledger: Chronological reporting per specific Customer or Vendor.

Module 4: System Administration: System setting configurations, base currency setup, toggle configurations for system roles, and simple raw sql database file backup utilities.

5. Execution Phase Directives for Cursor
Please process the development steps sequentially. Do not move to the next phase until instructed:

Phase 1: Migrations and Models
Create all database migration tables with indexing on frequently filtered keys (type, transaction_date, account_id, contact_id). Write corresponding Eloquent Models defining explicitly typed relationship methods (belongsTo, hasMany).

Phase 2: Contracts & Repositories
Generate all Repository Interface files and concrete Eloquent implementations for Account, Contact, and Transaction entities.

Phase 3: Financial Core Services
Write the unified TransactionService handling all dynamic atomic insertions, updates, balance checks, and cascading balance calculation deletions inside isolated database transaction blocks.

Phase 4: FormRequests & Thin Controllers
Construct validation files enforcing correct date fields, decimal entries, and ID validation checks. Write Controllers that pass validated inputs cleanly as structured arrays or DTO files into your backend services.

Phase 5: Tailwind Views & UI Layouts
Provide complete, functional front-end component implementations featuring searchable drop-downs, accessible UI tables, filtering toggles, and clean data layout forms.

---

## Getting Started

### Requirements
- PHP 8.3+
- Composer
- MySQL 8+

### Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB_* in .env
php artisan migrate --seed
php artisan serve
```

### Frontend Build (Tailwind + Vite + MingCute Icons)

```bash
composer require blade-ui-kit/blade-icons chrisoprea/blade-mingcute-icons
npm install
npm run dev    # development with hot reload
npm run build  # production assets
```

Icons are rendered via `<x-icon name="device.dashboard" />` (MingCute SVG set).

### Default Login
- **Email:** `admin@ledger.local`
- **Password:** `password`

### Environment
- `LEDGER_ALLOW_NEGATIVE_BALANCES=false` — reject expenses/transfers that exceed account balance

