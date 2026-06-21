# ExpenseBuddy

**Your Personal Finance Companion**

ExpenseBuddy is a self-hosted, multi-currency income and expense manager built with Laravel 13. Track accounts, transactions, lending, invoices, and analytics from a clean dashboard — with role-based access, PDF invoices, and installable PWA support for mobile.

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

---

## Features

### Core ledger
- **Dashboard** — income, expense, net balance, cash assets, and a 6-month trend chart
- **Transactions** — income, expense, and transfer entries with categories, payment methods, contacts, attachments, and multi-currency rates
- **Accounts** — multi-currency wallets/bank accounts with live balances
- **Transfers** — atomic double-entry style transfers between accounts

### Lending & contacts
- **People & companies** — track lenders, borrowers, customers, and vendors
- **Lending overview** — balances owed to you and by you
- **Contact ledger** — chronological history per contact

### Invoicing
- Generate **PDF invoices** from transactions
- **Shareable public links** for clients (`/i/{token}`)
- Branded with your system name and logo

### Analytics & reports
- **Detailed analytics** — category, time, and month breakdowns with charts
- **Income vs expense** — month-over-month comparison
- **Categorized reports** — spending and income by category

### Administration
- **System settings** — app name, logo (favicon + PWA icon), base currency, negative balance policy
- **Users & roles** — create users and assign roles
- **Menu permissions** — control sidebar access per role (16 granular permissions)
- **Category management** — income/expense categories with archive/restore
- **Currency management** — multiple currencies with exchange rates
- **Database backup** — download a SQL snapshot from the admin panel

### UX & mobile
- **Dark / light mode** with persistence
- **Responsive layout** — desktop sidebar + mobile bottom nav
- **Searchable selects**, modal forms, and confirmation dialogs
- **PWA install** — add to home screen from the login page (Chrome) with your uploaded logo as the app icon

---

## Tech stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13, PHP 8.3+ |
| Database | SQLite (default) or MySQL 8+ |
| Frontend | Blade, Tailwind CSS 3, Vite |
| Charts | Chart.js |
| PDF | DomPDF |
| Icons | MingCute (local SVG set) |

Architecture follows **Controller → Service → Repository** with strict typing, `decimal(15,4)` for money, and database transactions for balance mutations.

---

## Requirements

- PHP **8.3+** with extensions: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`
- Composer 2.x
- Node.js **18+** and npm (for frontend assets)
- SQLite **or** MySQL 8+

---

## Installation

### 1. Clone and install dependencies

```bash
git clone https://github.com/your-username/expensebuddy.git
cd expensebuddy

composer install
npm install
npm run build
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

**SQLite (recommended for local use):**

```bash
touch database/database.sqlite
```

Ensure `.env` contains:

```env
DB_CONNECTION=sqlite
```

**MySQL:** uncomment and set `DB_*` values in `.env`.

### 3. Database & storage

```bash
php artisan migrate
php artisan storage:link
```

> **No sample data is seeded by default.** You will configure everything through the web installer.

### 4. Web installer

Start the app:

```bash
php artisan serve
```

Open **`http://localhost:8000/install`** and complete the setup wizard:

1. **Branding** — app name + logo (required; becomes favicon and PWA icon)
2. **Administrator** — your login email and password
3. **Base currency** — e.g. USD, BDT, EUR

When finished, sign in at `/login`.

### 5. Production checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, and a real `APP_URL`
- Use HTTPS (required for PWA install on mobile)
- Point your web server document root to `/public`
- Run `php artisan config:cache` and `php artisan route:cache`

---

## Optional demo data

For local demos only, on a **fresh empty database**:

```bash
SEED_DEMO_DATA=true php artisan db:seed --class=DemoSeeder
```

This creates sample accounts, categories, payment methods, contacts, and a demo admin:

| Field | Value |
|-------|-------|
| Email | `admin@expensebuddy.test` |
| Password | `password` |

Upload a logo in **Admin → Settings** to set favicon and PWA icons when using demo seed.

---

## Development

```bash
# Terminal 1
php artisan serve

# Terminal 2 — hot reload for CSS/JS
npm run dev
```

Useful commands:

```bash
php artisan migrate:fresh          # reset database (re-run /install after)
php artisan db:seed                # prints install instructions (no demo data)
php artisan db:seed --class=MenuPermissionSeeder  # re-sync menu permissions
```

---

## Configuration

| Variable | Description |
|----------|-------------|
| `APP_NAME` | Laravel app name (default: ExpenseBuddy) |
| `APP_BRAND_NAME` | Default brand name before/without custom settings |
| `APP_BRAND_TAGLINE` | Tagline shown on login and PWA manifest |
| `LEDGER_ALLOW_NEGATIVE_BALANCES` | Allow expenses/transfers below zero balance |
| `SEED_DEMO_DATA` | Set `true` when running `db:seed` to load demo dataset |

Negative balance enforcement can also be toggled in **Admin → Settings**.

---

## Project structure (high level)

```
app/
├── Http/Controllers/     # Thin HTTP layer
├── Http/Requests/        # Validation
├── Models/               # Eloquent models
├── Repositories/         # Data access
├── Services/             # Business logic
└── Support/              # Brand, permissions, formatters

resources/views/          # Blade templates + components
routes/web.php            # Web routes
database/migrations/      # Schema
public/                   # Web root, PWA assets, built frontend
```

---

## PWA & favicon

- Upload your logo during **install** or in **Admin → Settings**
- That image is used for:
  - Browser tab favicon
  - Apple touch icon
  - PWA manifest icons (Chrome “Install app”)
- On the login screen, users who have not installed the app see an **Install** prompt (Chrome) or iOS **Add to Home Screen** hint

---

## Security notes

- Change default credentials immediately in production
- Installation is disabled once a user exists (visiting `/install` redirects to login)
- Keep `APP_DEBUG=false` in production
- Review role permissions before adding team members

---

## License

MIT

---

## Contributing

Issues and pull requests are welcome. Please open an issue before large changes.

**Suggested roadmap:** recurring transactions, bank import (CSV), API tokens, two-factor auth.
