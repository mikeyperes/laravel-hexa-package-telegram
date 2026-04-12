<?php

namespace hexa_package_telegram\Domains\Push;

use hexa_core\Models\User;
use hexa_package_telegram\Contracts\TelegramPushContract;
use hexa_package_telegram\Domains\Bot\TelegramBotClient;
use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\DeliveryLogs\TelegramDeliveryLogger;
use hexa_package_telegram\Domains\Recipients\TelegramRecipientResolver;
use hexa_package_telegram\DTOs\TelegramDeliveryResult;

class TelegramPushService implements TelegramPushContract
{
    public function __construct(
        protected TelegramBotClient $botClient,
        protected TelegramConfigRepository $config,
        protected TelegramRecipientResolver $recipients,
        protected TelegramDeliveryLogger $logger,
    ) {}

    public function isConfigured(): bool
    {
        return $this->config->isConfigured();
    }

    public function isHealthy(): bool
    {
        return $this->botClient->testBotToken()->success;
    }

    public function sendText(string $message, ?string $chatId = null, array $options = []): TelegramDeliveryResult
    {
        $targetChatId = $chatId ?: $this->config->getDefaultChatId();
        if (!$targetChatId) {
            return TelegramDeliveryResult::failure('No Telegram chat ID configured.');
        }

        return $this->sendTextToChatId($targetChatId, $message, $options);
    }

    public function sendTextToChatId(string $chatId, string $message, array $options = []): TelegramDeliveryResult
    {
        $result = $this->botClient->sendText($chatId, $message, $options);
        $this->logger->logDelivery('send_text', $chatId, $result);

        return $result;
    }

    public function sendTextToUser(User $user, string $message, array $options = []): TelegramDeliveryResult
    {
        $chatId = $this->recipients->resolveChatIdForUser($user);
        if ($chatId === null) {
            $result = TelegramDeliveryResult::failure('User does not have a Telegram chat ID linked.');
            $this->logger->logDelivery('send_text_user', (string) $user->getKey(), $result, [
                'user_id' => $user->getKey(),
            ]);

            return $result;
        }

        $result = $this->sendTextToChatId($chatId, $message, $options);
        $this->logger->logDelivery('send_text_user', (string) $user->getKey(), $result, [
            'user_id' => $user->getKey(),
        ]);

        return $result;
    }

    public function sendTextToUsers(iterable $users, string $message, array $options = []): array
    {
        $results = [];

        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $result = $this->sendTextToUser($user, $message, $options);
            $results[] = [
                'user_id' => $user->getKey(),
                'result'  => $result->toArray(),
            ];
        }

        return $results;
    }

    public function sendRichMessage(string $message, array $buttons, ?string $chatId = null, array $options = []): TelegramDeliveryResult
    {
        $targetChatId = $chatId ?: $this->config->getDefaultChatId();
        if (!$targetChatId) {
            return TelegramDeliveryResult::failure('No Telegram chat ID configured.');
        }

        return $this->sendRichMessageToChatId($targetChatId, $message, $buttons, $options);
    }

    public function sendRichMessageToChatId(string $chatId, string $message, array $buttons, array $options = []): TelegramDeliveryResult
    {
        $result = $this->botClient->sendRichMessage($chatId, $message, $buttons, $options);
        $this->logger->logDelivery('send_rich', $chatId, $result);

        return $result;
    }
}
