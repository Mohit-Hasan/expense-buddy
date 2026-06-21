<?php

declare(strict_types=1);

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/favicon.ico', [PwaController::class, 'favicon'])->name('pwa.favicon');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'menu.permission'])->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transfers/create', [TransactionController::class, 'createTransfer'])->name('transfers.create');
    Route::post('/transfers', [TransactionController::class, 'storeTransfer'])->name('transfers.store');
    Route::put('/transactions/{id}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    Route::prefix('transactions/{transactionId}/invoice')->name('transactions.invoice.')->group(function (): void {
        Route::get('/', [InvoiceController::class, 'show'])->name('show');
        Route::get('/pdf', [InvoiceController::class, 'download'])->name('pdf');
        Route::post('/share', [InvoiceController::class, 'share'])->name('share');
    });

    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::put('/accounts/{id}', [AccountController::class, 'update'])->name('accounts.update');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::post('/categories/{id}/archive', [CategoryController::class, 'archive'])->name('categories.archive');
    Route::post('/categories/{id}/restore', [CategoryController::class, 'restore'])->name('categories.restore');

    Route::redirect('/contacts', '/lending/people');
    Route::prefix('lending')->name('lending.')->group(function (): void {
        Route::get('/', [ContactController::class, 'overview'])->name('overview');
        Route::get('/ledger', [ContactController::class, 'ledger'])->name('ledger');
        Route::get('/people', [ContactController::class, 'index'])->name('people.index');
        Route::get('/people/create', [ContactController::class, 'create'])->name('people.create');
        Route::post('/people', [ContactController::class, 'store'])->name('people.store');
        Route::get('/people/{id}/edit', [ContactController::class, 'edit'])->name('people.edit');
        Route::put('/people/{id}', [ContactController::class, 'update'])->name('people.update');
    });

    Route::prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/income-vs-expense', [ReportController::class, 'incomeVsExpense'])->name('income-vs-expense');
        Route::get('/categorized', [ReportController::class, 'categorized'])->name('categorized');
        Route::get('/detailed', [ReportController::class, 'detailed'])->name('detailed');
        Route::redirect('/contact-ledger', '/lending/ledger');
    });

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', function () {
            $route = \App\Support\MenuPermissionRegistry::firstAdminRouteFor(auth()->user());

            return redirect()->route($route ?? 'dashboard');
        });
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::put('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::get('/currencies', [AdminController::class, 'currencies'])->name('currencies');
        Route::post('/currencies', [AdminController::class, 'storeCurrency'])->name('currencies.store');
        Route::put('/currencies/{id}', [AdminController::class, 'updateCurrency'])->name('currencies.update');
        Route::delete('/currencies/{id}', [AdminController::class, 'destroyCurrency'])->name('currencies.destroy');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/roles', [AdminController::class, 'roles'])->name('roles');
        Route::post('/roles', [AdminController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{id}', [AdminController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{id}', [AdminController::class, 'destroyRole'])->name('roles.destroy');
        Route::get('/backup', [AdminController::class, 'backupDatabase'])->name('backup');
    });
});

Route::get('/i/{token}', [InvoiceController::class, 'publicShow'])->name('invoices.public');
Route::get('/i/{token}/pdf', [InvoiceController::class, 'publicDownload'])->name('invoices.public.pdf');
