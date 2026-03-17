<?php

namespace hexa_package_telegram\Providers;

use Illuminate\Support\ServiceProvider;
use hexa_package_telegram\Services\TelegramService;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TelegramService::class);
    }

    public function boot(): void {}
}
