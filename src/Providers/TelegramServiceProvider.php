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

        // Sidebar links — package-owned and auto-wired into the core registry.
        $registry = app(\hexa_core\Services\PackageRegistryService::class);
        $registry->registerSidebarLink('telegram.index', 'Telegram', 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8', 'Sandbox', 'telegram', 82);
        if (method_exists($registry, 'registerPackage')) {
            $registry->registerPackage('telegram', 'hexawebsystems/laravel-hexa-package-telegram', [
            'title' => 'Telegram',
            'docsSlug' => 'telegram',
            'instructions' => [
                'Create a Telegram bot with BotFather and save the bot token in the host app Telegram settings.',
            ],
            'apiLinks' => [
                ['label' => 'BotFather', 'url' => 'https://t.me/BotFather'],
                ['label' => 'Telegram Bot API', 'url' => 'https://core.telegram.org/bots/api'],
            ],
            ]);
        }
    
        // Documentation
        if (class_exists(\hexa_core\Services\DocumentationService::class)) {
            app(\hexa_core\Services\DocumentationService::class)->register('telegram', 'Telegram Bot', 'hexawebsystems/laravel-hexa-package-telegram', [
                ['title' => 'Overview', 'content' => '<p>Telegram Bot API integration for notifications and 2FA verification.</p>'],
            ]);
        }
    }
}
