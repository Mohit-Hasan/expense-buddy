# ExpenseBuddy

**Your Personal Finance Companion**

<div align="center">

## ⭐ Star this repo if you find ExpenseBuddy useful

#### It takes one second — and it helps others discover the project, keeps development going, and shows that self-hosted finance tools matter.

[![⭐ Star on GitHub](https://img.shields.io/badge/⭐_Star_ExpenseBuddy-FFD700?style=for-the-badge&logo=github&logoColor=000000&labelColor=fff8dc)](https://github.com/Mohit-Hasan/expense-buddy/stargazers)

**Like what you see?** Tap **Star** above ↑ before you install — every star genuinely helps.

</div>

---

ExpenseBuddy is a self-hosted finance app for tracking income, expenses, accounts, lending, and invoices — with multi-currency support, role-based access, and a mobile-friendly installable web app.

No subscription. Upload to your server, run the web installer, and start managing money in minutes.

---

## Features

### Dashboard & transactions
- Record **income** and **expense** with date, category, payment method, notes, and references
- Live dashboard totals: income, expense, net balance, and cash on hand
- Search and filter your ledger

### Accounts & transfers
- Create **bank, cash, or wallet** accounts in different currencies
- **Transfer** money between accounts — balances update automatically
- Optional policy to **block negative balances**

### Categories & reports
- Separate **income** and **expense** categories (archive/restore supported)
- **Income vs expense** report
- **Categorized** breakdown and **detailed analytics** with charts

### Invoices
- Generate **PDF invoices** from any transaction
- Share a **public link** so clients can view or download

### Multi-currency
- Choose a **base currency** at install time
- Add currencies with exchange rates
- Transactions stay in the account’s native currency; reports convert to base

### Lending
- Track people and companies you lend to or borrow from
- Lending overview and per-contact ledger

### Team & administration
- **Users, roles, and per-menu permissions**
- **System settings**: app name, logo, base currency, ledger rules
- **Email settings**: SMTP or PHP Mail — used for password reset emails
- **Database backup** download (MySQL)
- **Currency management**

### Security & sign-in
- **Forgot password** flow with email reset link
- **Two-factor authentication (2FA)** — any user can enable TOTP with QR code under **Account → Security**
- **30-day sessions** — “Keep me signed in” on the login page
- Branded login with optional uploaded logo, or built-in wallet icon fallback

### Branding & PWA
- Upload a **logo** in admin — used in sidebar, favicon, and mobile home-screen icon
- If no logo is set, a **built-in wallet SVG** is used everywhere (no broken/black placeholders)
- **Install as app** prompt on login (Chrome / mobile) — works best over HTTPS

---

## Requirements

| Requirement | Notes |
|-------------|--------|
| **PHP 8.3+** | With PDO, OpenSSL, Mbstring, Tokenizer, XML, Fileinfo, GD |
| **MySQL / MariaDB** | Recommended for live hosting |
| **SQLite** | Fine for local testing |
| **Web server** | Apache (mod_rewrite) or Nginx — document root must be `/public` |
| **Writable folders** | `storage/`, `bootstrap/cache/`, project root (for `.env`) |

The release package includes **`vendor/`** and **`public/build/`** — no Composer or npm is required on the server.

---

## Install on a live server

### 1. Upload the files
1. Upload the full project folder to your hosting (cPanel, FTP, etc.)
2. Set the domain **document root** to the **`public`** folder  
   *(Not the project root — point directly at `/public`.)*

If your host only allows the project root as document root, a root `.htaccess` is included that forwards requests to `public/`.

### 2. Set permissions
Make these writable by PHP (typically `755` or `775`):

```
storage/
bootstrap/cache/
```

The project root must also allow creating `.env` during install.

### 3. Create a MySQL database
In your hosting panel, create:
- A database
- A database user with full privileges on that database

### 4. Run the web installer
Open in your browser:

```
https://yourdomain.com/install/
```

| Step | What happens |
|------|----------------|
| **1. Requirements** | PHP version, extensions, vendor folder, built assets, writable paths |
| **2. Database** | MySQL credentials + site URL → `.env` is created automatically |
| **3. Application** | Admin account, app name, optional logo, base currency, demo data on/off |
| **4. Finish** | Fresh migration, storage link, admin user, optional demo data |

**Install options:**
- **Logo** — optional; skip to use the built-in wallet icon
- **Demo data** — sample accounts, categories, payment methods, and contacts (your admin login is still the one you enter)
- **Fresh reinstall** — checking “confirm reinstall” wipes the database and starts over

> Every install runs **`migrate:fresh`** for a clean database.

### 5. After install
1. Log in at `https://yourdomain.com/login`
2. Configure **Administration → Settings** (email, branding, currency)
3. **Delete the `/public/install` folder** from your server

---

## Install locally

### Option A — Web installer (easiest)

```bash
php artisan serve
```

Open `http://127.0.0.1:8000/install/`, choose **SQLite** on the database step, and complete the wizard.

### Option B — Developer seed (no installer)

```bash
cp .env.example .env
php artisan key:generate
```

Add to `.env`:

```env
SEED_DEV_ADMIN=true
SEED_ADMIN_EMAIL=admin@expensebuddy.test
SEED_ADMIN_PASSWORD=password
```

Then:

```bash
php artisan db:seed
```

Default login: **admin@expensebuddy.test** / **password**

Optional demo data:

```env
SEED_DEMO_DATA=true
```

### Option C — From source (no vendor yet)

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Then use **Option A** (`/install/`) or **Option B** (`db:seed`).

---

## First-time setup inside the app

| Task | Where |
|------|--------|
| App name, logo, base currency | **Administration → Settings** |
| SMTP / PHP Mail for emails | **Administration → Settings → Email Configuration** |
| Add currencies | **Administration → Currencies** |
| Users & roles | **Administration → Users / Roles** |
| Enable 2FA | **Account → Security** (sidebar) |
| Reset password | **Forgot password?** on login page |

### Email (password reset)
1. Go to **Administration → Settings → Email Configuration**
2. Choose **SMTP** (Gmail, Outlook, Mailtrap, etc.) or **PHP Mail**
3. Fill in host, port, credentials, and “From” address
4. Save, then test with **Forgot password** on the login page

---

## Configuration reference

Most day-to-day settings are in the admin panel. The installer creates `.env` with these common variables:

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Full site URL (no trailing slash) — must match how users access the site |
| `APP_DEBUG` | `false` on live servers |
| `APP_ENV` | `production` on live servers |
| `DB_*` | Database connection |
| `SESSION_LIFETIME` | Session length in minutes (default `43200` = 30 days) |
| `LEDGER_ALLOW_NEGATIVE_BALANCES` | Allow accounts to go below zero |

Email is configured in the admin UI (stored in the database), not in `.env`.

---

## Troubleshooting

### Logo or favicon shows a black box
- Upload a real logo under **Administration → Settings**, or leave logo empty to use the built-in wallet SVG
- Ensure the storage symlink exists:

```bash
php artisan storage:link
```

- Hard-refresh the browser (`Cmd+Shift+R` / `Ctrl+Shift+R`) — favicons are heavily cached

### `/storage/...` images return 404
```bash
php artisan storage:link
```

### App works but installer still shows “not installed”
If you created the admin via `db:seed` instead of the web installer:

```bash
php artisan expensebuddy:repair-install-lock
php artisan storage:link
```

### 404 on all pages except `/install/`
- Document root must be **`public/`**
- On Apache, enable **mod_rewrite**
- On Nginx, use `try_files $uri $uri/ /index.php?$query_string;`

### Styles missing / blank UI
Ensure `public/build/manifest.json` exists. If you cloned from Git without assets:

```bash
npm install && npm run build
```

---

## Testing (developers)

```bash
# PHPUnit (unit + feature + route smoke)
composer test

# Playwright end-to-end (app flows)
npm run test:e2e

# Playwright install wizard
npm run test:e2e:install

# Full local gate
composer test:all
```

First-time Playwright setup:

```bash
npm install
npx playwright install chromium
```

CI runs automatically on push/PR via GitHub Actions (`.github/workflows/tests.yml`).

E2E tests use port **8765** with `artisan serve --no-reload`.

---

## Security checklist

- [ ] Delete **`/public/install`** after setup
- [ ] Use **HTTPS** on live sites (required for PWA install on mobile)
- [ ] Set **`APP_DEBUG=false`** in production
- [ ] Use a strong administrator password
- [ ] Enable **2FA** for admin accounts on shared or production servers
- [ ] Configure **SMTP** so password reset emails work reliably

---

## Project structure (quick reference)

```
public/           ← Web root (point your domain here)
public/install/   ← Standalone web installer (delete after setup)
public/build/     ← Compiled CSS/JS (included in release)
storage/          ← Logs, uploads, sessions (must be writable)
database/         ← Migrations & seeders
```

**Admin is not in `DatabaseSeeder` by default** — it is created by the web installer or `SEED_DEV_ADMIN=true php artisan db:seed`.

Demo sample data only: `php artisan db:seed --class=DemoDataSeeder` *(requires an existing install with admin + currency)*.

---

## License

MIT
