# ExpenseBuddy

**Your Personal Finance Companion**

ExpenseBuddy helps you track income, expenses, accounts, and cash flow in one place — with multi-currency support, clear reports, and a mobile-friendly installable app.

---

## What you can do

### Money in & out
- Record **income** and **expense** transactions with amount, date, category, and payment method
- Attach notes, references, and files to any transaction
- See totals on the dashboard: income, expense, net balance, and cash on hand

### Accounts & transfers
- Create **bank, cash, or wallet accounts** in different currencies
- Move money between accounts with **transfers** (balances update automatically)
- Optional rule to block spending below zero balance

### Categories & reports
- Organize spending with **income/expense categories**
- **Detailed analytics** with charts by category, month, and time
- **Income vs expense** summary and categorized breakdown reports

### Invoices
- Generate **PDF invoices** from transactions
- Share a **public link** with clients

### Multi-currency
- Set a **base currency** during installation
- Add more currencies with exchange rates
- Enter transactions in the account currency with rate at time of entry

### Team & settings
- **Administrator account** created during install
- **Users & roles** with per-menu permissions
- Upload your **logo** — used in the app, browser favicon, and mobile home-screen icon
- **PWA install** prompt on login for Chrome / mobile

### Lending (optional module)
- Track people and companies you lend to or borrow from
- Lending overview and per-contact ledger

---

## Install on a live server (upload ZIP)

The package is ready to upload **with `vendor/` and built frontend assets included**. No Composer or npm commands are required on the server.

### 1. Upload files
1. Download or zip the full project folder
2. Upload to your hosting (cPanel, FTP, etc.)
3. Point your domain document root to the **`/public`** folder

### 2. Folder permissions
Make sure these are writable by PHP:
- `/storage`
- `/bootstrap/cache`
- project root (so `.env` can be created)

### 3. Create database (MySQL)
In your hosting panel, create:
- A MySQL database
- A database user with full access to that database

### 4. Run the web installer
Open in your browser:

```
https://yourdomain.com/install/
```

Follow the 4 steps:

| Step | What it does |
|------|----------------|
| **Requirements** | Checks PHP version, extensions, vendor folder, writable folders |
| **Database** | MySQL credentials + your site URL → creates `.env` automatically |
| **Application** | Admin login, app name, logo, base currency, demo data on/off |
| **Finish** | Runs fresh migration, storage link, and setup |

**Install options:**
- **Without demo data** — clean ledger; you add your own accounts and categories
- **With demo data** — sample accounts, categories, payment methods, and contacts (your admin login is still the one you enter)

Every install runs a **fresh database migration** so you always start clean.

### 5. After install
1. Log in at `https://yourdomain.com/login`
2. **Delete the `/public/install` folder** from your server for security

---

## Install locally

Same web installer — no terminal setup required if `vendor/` is included.

1. Place the project folder on your machine
2. Start PHP built-in server from the project root:

```bash
php artisan serve
```

3. Open `http://127.0.0.1:8000/install/`
4. Choose **SQLite** on the database step for the quickest local test
5. Complete the wizard and log in

---

## Optional: developer setup from source

If you clone from GitHub without `vendor/`:

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Then use `/install/` — the installer still creates `.env`, migrates, and links storage for you.

To load demo sample data manually on an already-installed app:

```bash
php artisan db:seed --class=DemoDataSeeder
```

---

## Configuration (after install)

Most settings are in **Admin → Settings** inside the app.

Environment variables in `.env` (created by installer):

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Your site URL |
| `APP_DEBUG` | `false` on live servers |
| `DB_*` | Database connection |
| `LEDGER_ALLOW_NEGATIVE_BALANCES` | Allow spending below zero |

---

## Security notes

- Delete `/public/install` after successful setup
- Use HTTPS on live sites (needed for mobile app install)
- Use a strong administrator password
- Keep `APP_DEBUG=false` in production

---

## License

MIT
