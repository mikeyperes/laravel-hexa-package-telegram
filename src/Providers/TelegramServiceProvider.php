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

        // Sidebar links — registered via PackageRegistryService with auto permission checks
        if (!config('hexa.app_controls_sidebar', false)) {
            $registry = app(\hexa_core\Services\PackageRegistryService::class);
            $registry->registerSidebarLink('telegram.index', 'Telegram', 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8', 'Sandbox', 'telegram', 82);
        }
    
        // Documentation
        if (class_exists(\hexa_core\Services\DocumentationService::class)) {
            app(\hexa_core\Services\DocumentationService::class)->register('telegram', 'Telegram Bot', 'hexawebsystems/laravel-hexa-package-telegram', [
                ['title' => 'Overview', 'content' => '<p>Telegram Bot API integration for notifications and 2FA verification.</p>'],
            ]);
        }
}

}
