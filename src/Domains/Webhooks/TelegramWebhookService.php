<?php

namespace hexa_package_telegram\Domains\Webhooks;

use hexa_package_telegram\Domains\Bot\TelegramBotClient;
use hexa_package_telegram\Domains\TwoFactor\TelegramTwoFactorTransport;
use hexa_package_telegram\DTOs\TelegramDeliveryResult;

class TelegramWebhookService
{
    public function __construct(
        protected TelegramBotClient $botClient,
        protected TelegramTwoFactorTransport $twoFactor,
    ) {}

    public function setDefaultWebhook(): TelegramDeliveryResult
    {
        return $this->botClient->setWebhook(route('telegram.webhook'));
    }

    public function setWebhook(string $url): TelegramDeliveryResult
    {
        return $this->botClient->setWebhook($url);
    }

    /**
     * @return array<string, mixed>
     */
    public function getWebhookInfo(): array
    {
        return $this->botClient->getWebhookInfo();
    }

    public function handleIncomingUpdate(array $payload): void
    {
        $this->twoFactor->handleWebhook($payload);
    }
}
