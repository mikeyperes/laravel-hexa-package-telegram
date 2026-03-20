<?php

use hexa_package_telegram\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Telegram Package Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'locked', 'system_lock', 'two_factor', 'role'])->group(function () {
    // Raw dev view
    Route::get('/raw-telegram', [TelegramController::class, 'raw'])->name('telegram.index');

    // AJAX endpoints
    Route::post('/telegram/send', [TelegramController::class, 'send'])->name('telegram.send');
    Route::post('/telegram/webhook-info', [TelegramController::class, 'webhookInfo'])->name('telegram.webhook-info');
    Route::post('/telegram/set-webhook', [TelegramController::class, 'setWebhook'])->name('telegram.set-webhook');
});
