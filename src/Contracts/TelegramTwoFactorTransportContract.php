<?php

namespace hexa_package_telegram\Contracts;

use hexa_core\Models\User;
use hexa_package_telegram\DTOs\TelegramDeliveryResult;

interface TelegramTwoFactorTransportContract
{
    public function isReady(): bool;

    public function getBotUsername(): string;

    /**
     * @return array{success: bool, bot_link?: string, token?: string, error?: string}
     */
    public function beginLink(User $user): array;

    public function isLinked(User $user): bool;

    public function sendChallengeCode(User $user, string $code): TelegramDeliveryResult;

    public function handleWebhook(array $payload): void;
}
