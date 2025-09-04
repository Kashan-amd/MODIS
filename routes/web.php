<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    // VOLT dashboard
    Volt::route('dashboard', 'dashboard')->name('dashboard');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // Transactions routes
    Volt::route('accounts/transactions', 'transactions')->name('accounts.transactions');
    Route::get('accounts/transactions/print-ledger', [\App\Http\Controllers\TransactionsPdfController::class, 'printLedger'])->name('accounts.transactions.print-ledger');

    // Accounting routes
    Volt::route('accounts/chart-of-accounts', 'accounting.chart-of-accounts')->name('accounts.chart-of-accounts');
    Volt::route('accounts/transaction-entries', 'accounting.transaction-entries')->name('accounts.transaction-entries');
    Volt::route('accounts/financial-reports', 'accounting.financial-reports')->name('accounts.financial-reports');
    Volt::route('accounts/petty-cash', 'accounting.petty-cash')->name('accounts.petty-cash');

    // Admin routes
    Volt::route('admin/organizations', 'admin.organizations')->name('admin.organizations');
    Volt::route('admin/clients', 'admin.clients')->name('admin.clients');
    Volt::route('admin/vendors', 'admin.vendors')->name('admin.vendors');
    Volt::route('admin/items', 'admin.items')->name('admin.items');
    Volt::route('admin/opening-balances', 'admin.opening-balances')->name('admin.opening-balances');

    // job booking sales route
    Volt::route('sales/job-booking', 'sales.job-booking')->name('sales.job-booking');
    Volt::route('sales/costing', 'sales.costing')->name('sales.costing');

    // Print routes
    Route::get('print/client-invoice/{jobId}', function ($jobId) {
        $job = \App\Models\JobBooking::with([
            'client',
            'organization',
            'jobCostings.vendor',
            'jobCostings.subAccount',
            'jobCostings.subItem'
        ])->findOrFail($jobId);

        return view('print.client-invoice', compact('job'));
    })->name('print.client-invoice');
});

require __DIR__ . '/auth.php';
