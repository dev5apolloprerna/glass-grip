<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NumberSettingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

// Authenticated routes
  Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Quotations - accessible to both roles (user creates/manages own, super_admin sees all)
    Route::resource('quotations', QuotationController::class);
    Route::post('quotations/{quotation}/approve', [QuotationController::class, 'approve'])->name('quotations.approve');
    Route::post('quotations/{quotation}/reject', [QuotationController::class, 'reject'])->name('quotations.reject');
    Route::post('quotations/{quotation}/duplicate', [QuotationController::class, 'duplicate'])->name('quotations.duplicate');

    Route::get('ajax/last-price', [QuotationController::class, 'lastPrice'])->name('quotations.last-price');

    // Invoices
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');

    // Payment collection against a specific invoice
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');


    // Customer ledger entries - both roles may enter payments
    Route::post('customers/{customer}/ledger', [CustomerController::class, 'storeLedgerEntry'])->name('customers.ledger.store');
   // Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    // Super Admin only
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('customers', CustomerController::class)->except(['show']);
        Route::resource('products', ProductController::class);
        Route::resource('users', UserController::class)->except(['show']);

        Route::get('number-settings', [NumberSettingController::class, 'index'])->name('number-settings.index');
        Route::put('number-settings/{numberSetting}', [NumberSettingController::class, 'update'])->name('number-settings.update');

        Route::get('reports/customer-ledger', [ReportController::class, 'customerLedger'])->name('reports.customer-ledger');
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    });
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
});
