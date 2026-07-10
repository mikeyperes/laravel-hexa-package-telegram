<?php

namespace hexa_package_telegram\Domains\Webhooks;

use hexa_package_telegram\Domains\Bot\TelegramBotClient;
use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\TwoFactor\TelegramTwoFactorTransport;
use hexa_package_telegram\DTOs\TelegramDeliveryResult;

class TelegramWebhookService
{
    public function __construct(
        protected TelegramBotClient $botClient,
        protected TelegramConfigRepository $config,
        protected TelegramTwoFactorTransport $twoFactor,
        protected TelegramInboundRouter $router,
    ) {}

    public function setDefaultWebhook(?string $botKey = null): TelegramDeliveryResult
    {
        return $this->botClient->setWebhook(route("telegram.webhook"), $botKey);
    }

    public function setWebhook(string $url, ?string $botKey = null): TelegramDeliveryResult
    {
        return $this->botClient->setWebhook($url, $botKey);
    }

    public function getWebhookInfo(?string $botKey = null): array
    {
        return $this->botClient->getWebhookInfo($botKey);
    }

    public function handleIncomingUpdate(array $payload, ?string $botKey = null): void
    {
        $this->captureInboundChat($payload, $botKey);

        if ($this->router->dispatch($payload)) {
            return;
        }

        $this->twoFactor->handleWebhook($payload);
    }

    protected function captureInboundChat(array $payload, ?string $botKey = null): void
    {
        $chat = $payload["message"]["chat"]
            ?? $payload["edited_message"]["chat"]
            ?? $payload["callback_query"]["message"]["chat"]
            ?? null;

        if (is_array($chat)) {
            $this->config->rememberInboundChat($chat, $botKey);
        }
    }
}
