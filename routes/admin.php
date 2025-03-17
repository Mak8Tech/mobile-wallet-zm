<?php

use Illuminate\Support\Facades\Route;
use Mak8Tech\MobileWalletZm\Http\Controllers\AdminController;

// Admin routes with authorization and rate limiting
Route::middleware(['auth', 'mobile-wallet.admin', 'rate.limit:admin'])->prefix('admin/mobile-wallet')->name('mobile-wallet.admin.')->group(function () {
    // Transaction listing and filtering
    Route::get('transactions', [AdminController::class, 'index'])
        ->name('transactions.index')
        ->middleware('mobile-wallet.admin:mobile-wallet.transactions.view');

    // Single transaction view
    Route::get('transactions/{transaction}', [AdminController::class, 'show'])
        ->name('transactions.show')
        ->middleware('mobile-wallet.admin:mobile-wallet.transactions.view');

    // Update transaction
    Route::put('transactions/{transaction}', [AdminController::class, 'update'])
        ->name('transactions.update')
        ->middleware('mobile-wallet.admin:mobile-wallet.transactions.manage');

    // Generate report
    Route::get('reports/transactions', [AdminController::class, 'report'])
        ->name('reports.transactions')
        ->middleware('mobile-wallet.admin:mobile-wallet.transactions.view');
});
