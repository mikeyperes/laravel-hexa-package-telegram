<?php

namespace hexa_package_telegram\Providers;

use hexa_package_telegram\Contracts\TelegramPushContract;
use hexa_package_telegram\Contracts\TelegramTwoFactorTransportContract;
use hexa_package_telegram\Domains\Bot\TelegramBotClient;
use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\DeliveryLogs\TelegramDeliveryLogger;
use hexa_package_telegram\Domains\Push\TelegramPushService;
use hexa_package_telegram\Domains\Recipients\TelegramRecipientResolver;
use hexa_package_telegram\Domains\TwoFactor\TelegramTwoFactorTransport;
use hexa_package_telegram\Domains\Webhooks\TelegramWebhookService;
use Illuminate\Support\ServiceProvider;
use hexa_package_telegram\Services\TelegramService;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/telegram.php', 'telegram');
        $this->app->singleton(TelegramConfigRepository::class);
        $this->app->singleton(TelegramBotClient::class);
        $this->app->singleton(TelegramRecipientResolver::class);
        $this->app->singleton(TelegramDeliveryLogger::class);
        $this->app->singleton(TelegramWebhookService::class);
        $this->app->singleton(TelegramPushContract::class, TelegramPushService::class);
        $this->app->singleton(TelegramTwoFactorTransportContract::class, TelegramTwoFactorTransport::class);
        $this->app->singleton(TelegramPushService::class, TelegramPushService::class);
        $this->app->singleton(TelegramTwoFactorTransport::class, TelegramTwoFactorTransport::class);
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

        $registry = app(\hexa_core\Services\PackageRegistryService::class);
        // HWS-SIDEBAR-MENU-3L-BEGIN
        $registry->registerDomainGroup('Discovery', 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 20);
        $registry->registerSectionGroup('Sandbox', 'Discovery', '', 20);
        // HWS-SIDEBAR-MENU-3L-END

        $registry->registerSidebarLink('telegram.index', 'Telegram', 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8', 'Sandbox', 'telegram', 82);
        $registry->registerSidebarSettingsLink('Telegram', 'settings.telegram', 42);
        if (method_exists($registry, 'registerPackage')) {
            $registry->registerPackage('telegram', 'hexawebsystems/laravel-hexa-package-telegram', [
                'title' => 'Telegram',
                'color' => 'sky',
                'icon' => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
                'settingsRoute' => 'settings.telegram',
                'settingsShellClass' => 'max-w-4xl',
                'docsSlug' => 'telegram',
                'instructions' => [
                    'Create a Telegram bot with BotFather.',
                    'Store the bot token and username in the Telegram package settings page.',
                    'Set the webhook and enable Telegram as an allowed 2FA method from system settings.',
                ],
                'apiLinks' => [
                    ['label' => 'BotFather', 'url' => 'https://t.me/BotFather'],
                    ['label' => 'Telegram Bot API', 'url' => 'https://core.telegram.org/bots/api'],
                ],
            ]);
        }

        if (method_exists($registry, 'registerPermissions')) {
            $registry->registerPermissions('telegram', [
                'groups' => [
                    'Telegram' => ['settings.telegram*', 'telegram.*'],
                ],
                'roleDefaults' => [
                    'admin' => ['settings.telegram*', 'telegram.*'],
                ],
            ]);
        }

        $this->registerPermissions();

        if (class_exists(\hexa_core\Services\DocumentationService::class)) {
            app(\hexa_core\Services\DocumentationService::class)->register('telegram', 'Telegram Bot', 'hexawebsystems/laravel-hexa-package-telegram', [
                ['title' => 'Overview', 'content' => '<p>Canonical Telegram package for bot configuration, webhook handling, Telegram-based 2FA transport, and reusable outbound push notifications.</p>'],
            ]);
        }
    }

    private function registerPermissions(): void
    {
        $permissions = config('hws.role_permissions', []);
        $telegramRoutes = ['settings.telegram*', 'telegram.*'];

        foreach (['admin'] as $role) {
            $permissions[$role] = array_values(array_unique(array_merge($permissions[$role] ?? [], $telegramRoutes)));
        }

        config(['hws.role_permissions' => $permissions]);
    }
}
