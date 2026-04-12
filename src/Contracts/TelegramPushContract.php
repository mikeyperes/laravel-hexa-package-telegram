<?php

namespace hexa_package_telegram\Contracts;

use hexa_core\Models\User;
use hexa_package_telegram\DTOs\TelegramDeliveryResult;

interface TelegramPushContract
{
    public function isConfigured(): bool;

    public function isHealthy(): bool;

    public function sendText(string $message, ?string $chatId = null, array $options = []): TelegramDeliveryResult;

    public function sendTextToChatId(string $chatId, string $message, array $options = []): TelegramDeliveryResult;

    public function sendTextToUser(User $user, string $message, array $options = []): TelegramDeliveryResult;

    /**
     * @param iterable<User> $users
     * @return array<int, array{user_id: int|null, result: array{success: bool, message: string, data: array|null}}>
     */
    public function sendTextToUsers(iterable $users, string $message, array $options = []): array;

    public function sendRichMessage(string $message, array $buttons, ?string $chatId = null, array $options = []): TelegramDeliveryResult;

    public function sendRichMessageToChatId(string $chatId, string $message, array $buttons, array $options = []): TelegramDeliveryResult;
}
