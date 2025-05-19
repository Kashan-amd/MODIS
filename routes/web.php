<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function ()
{
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function ()
{
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // Transactions routes
    Volt::route('accounts/transactions', 'transactions')->name('accounts.transactions');
    Route::get('accounts/transactions/print-ledger', [\App\Http\Controllers\TransactionsPdfController::class, 'printLedger'])->name('accounts.transactions.print-ledger');

    // Admin routes
    Volt::route('admin/organizations', 'admin.organizations')->name('admin.organizations');
    Volt::route('admin/clients', 'admin.clients')->name('admin.clients');
    Volt::route('admin/vendors', 'admin.vendors')->name('admin.vendors');
    Volt::route('admin/items', 'admin.items')->name('admin.items');
    Volt::route('admin/opening-balances', 'admin.opening-balances')->name('admin.opening-balances');

    // job booking sales route
    Volt::route('sales/job-booking', 'sales.job-booking')->name('sales.job-booking');
});

require __DIR__ . '/auth.php';
