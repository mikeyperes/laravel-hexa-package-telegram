<?php

namespace hexa_package_telegram\Providers;

use Illuminate\Support\ServiceProvider;
use hexa_package_telegram\Services\TelegramService;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/telegram.php', 'telegram');
        $this->app->singleton(TelegramService::class);
    }

    /**
     * Bootstrap package resources.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/telegram.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'telegram');
        $this->registerSidebarItems();
    }

    /**
     * Push sidebar menu items into core layout stacks.
     *
     * @return void
     */
    private function registerSidebarItems(): void
    {
        view()->composer('layouts.app', function ($view) {
            if (config('hexa.app_controls_sidebar', false)) return;
            $view->getFactory()->startPush('sidebar-sandbox', view('telegram::partials.sidebar-menu')->render());
        });
    }
}
