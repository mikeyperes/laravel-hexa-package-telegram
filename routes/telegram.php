<?php

use hexa_package_telegram\Http\Controllers\TelegramSettingController;
use hexa_package_telegram\Http\Controllers\TelegramController;
use hexa_package_telegram\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post("/telegram/webhook", [TelegramWebhookController::class, "handle"])
    ->middleware('throttle:webhook')
    ->name("telegram.webhook");

Route::middleware(["web", "auth", "locked", "system_lock", "two_factor", "role"])->group(function () {
    Route::get("/settings/telegram", [TelegramSettingController::class, "index"])->name("settings.telegram");
    Route::post("/settings/telegram", [TelegramSettingController::class, "save"])->name("settings.telegram.update");
    Route::post("/settings/telegram/test", [TelegramSettingController::class, "test"])->name("settings.telegram.test");
    Route::post("/settings/telegram/webhook", [TelegramSettingController::class, "setWebhook"])->name("settings.telegram.webhook");
    Route::get("/settings/telegram/webhook-info", [TelegramSettingController::class, "webhookInfo"])->name("settings.telegram.webhook-info");
    Route::post("/settings/telegram/bots/{botKey}/activate", [TelegramSettingController::class, "activate"])->name("settings.telegram.bots.activate");
    Route::post("/settings/telegram/bots/{botKey}/test", [TelegramSettingController::class, "test"])->name("settings.telegram.bots.test");
    Route::post("/settings/telegram/bots/{botKey}/webhook", [TelegramSettingController::class, "setWebhook"])->name("settings.telegram.bots.webhook");
    Route::get("/settings/telegram/bots/{botKey}/webhook-info", [TelegramSettingController::class, "webhookInfo"])->name("settings.telegram.bots.webhook-info");

    Route::hexaRawPage("/raw-telegram", [TelegramController::class, "raw"], "telegram.index", [
        "package" => "telegram",
        "label" => "Console",
        "sortOrder" => 10,
    ]);

    Route::post("/telegram/send", [TelegramController::class, "send"])->name("telegram.send");
    Route::post("/telegram/webhook-info", [TelegramController::class, "webhookInfo"])->name("telegram.webhook-info");
    Route::post("/telegram/set-webhook", [TelegramController::class, "setWebhook"])->name("telegram.set-webhook");
});
