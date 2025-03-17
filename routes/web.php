<?php

use Illuminate\Support\Facades\Route;
use Mak8Tech\MobileWalletZm\Http\Controllers\{WebhookController, MobileWalletController};
use Mak8Tech\MobileWalletZm\Http\Middleware\VerifyWebhook;

// Public routes for webhooks
Route::post('api/mobile-wallet/webhook/{provider?}', [WebhookController::class, 'handle'])
    ->name('mobile-wallet.webhook')
    ->middleware(VerifyWebhook::class);

// Payment routes
Route::post('api/mobile-wallet/payment', [MobileWalletController::class, 'initiatePayment'])
    ->name('mobile-wallet.payment.initiate');

Route::get('api/mobile-wallet/payment/{transactionId}/status', [MobileWalletController::class, 'checkStatus'])
    ->name('mobile-wallet.payment.status');
