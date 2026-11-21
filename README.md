# .

Telegram bot notifications and commands for the Hexa Core framework.

## Identity

- Kind: Laravel support package
- Version: `4.1.0`
- Composer name: `hexawebsystems/laravel-hexa-package-telegram`
- PHP namespace: `hexa_package_telegram`
- Requires: `php` ^8.2
- Installed: in a Hexa Core app, `packages/hexawebsystems/.`

## Features

- **TelegramController** (`src/Http/Controllers/TelegramController.php`): raw/debug tooling for Telegram transport.

## How it is used

Pages and endpoints:

- `POST /telegram/webhook/{botKey}` (`telegram.webhook.account`)

## Settings

Config `config/telegram.php`: `version`, `inbound_handlers`, `webhook_secret_token`, `webhook_max_payload_bytes`, `webhook_throttle_per_minute`.

## What it calls

- Outside services: `api.telegram.org`, `core.telegram.org`, `t.me`.

## Code layout

- Service provider: `hexa_package_telegram\Providers\TelegramServiceProvider`.
- Folders: `src/Contracts/`, `src/DTOs/`, `src/Domains/Bot/`, `src/Domains/Config/`, `src/Domains/DeliveryLogs/`, `src/Domains/Push/`, `src/Domains/Recipients/`, `src/Domains/TwoFactor/`, `src/Domains/Webhooks/`, `src/Http/Controllers/`, `src/Providers/`, `src/Services/`.
- Views: `resources/views/` (2 entries).

## Tests

`tests/` holds 3 test file(s). Run them from the host app with `php artisan test packages/hexawebsystems/./tests`.
