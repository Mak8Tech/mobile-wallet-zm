<?php

use Illuminate\Support\Facades\Route;
use Mak8Tech\MobileWalletZm\Http\Controllers\MobileWalletController;
use Mak8Tech\MobileWalletZm\Http\Controllers\WebhookController;
use Mak8Tech\MobileWalletZm\Http\Middleware\VerifyWebhook;

// Public routes for webhooks
Route::middleware(['rate.limit:webhook', VerifyWebhook::class])->group(function () {
    Route::post('api/mobile-wallet/webhook/mtn', [WebhookController::class, 'handleMTN'])
        ->name('mobile-wallet.webhook.mtn')
        ->middleware('verify.webhook.signature:mtn');

    Route::post('api/mobile-wallet/webhook/airtel', [WebhookController::class, 'handleAirtel'])
        ->name('mobile-wallet.webhook.airtel')
        ->middleware('verify.webhook.signature:airtel');

    Route::post('api/mobile-wallet/webhook/zamtel', [WebhookController::class, 'handleZamtel'])
        ->name('mobile-wallet.webhook.zamtel')
        ->middleware('verify.webhook.signature:zamtel');
});

// Payment routes with CSRF protection
Route::middleware(['rate.limit:payment', 'mobile-wallet.csrf'])->group(function () {
    Route::post('api/mobile-wallet/payment', [MobileWalletController::class, 'initiatePayment'])
        ->name('mobile-wallet.payment.initiate');
});

// Status check routes
Route::middleware(['rate.limit:status', 'mobile-wallet.csrf'])->group(function () {
    Route::get('api/mobile-wallet/payment/{transactionId}/status', [MobileWalletController::class, 'checkStatus'])
        ->name('mobile-wallet.payment.status');
});
