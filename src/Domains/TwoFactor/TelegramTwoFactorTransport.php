<?php

namespace hexa_package_telegram\Domains\TwoFactor;

use hexa_core\Models\User;
use hexa_package_telegram\Contracts\TelegramPushContract;
use hexa_package_telegram\Contracts\TelegramTwoFactorTransportContract;
use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\DeliveryLogs\TelegramDeliveryLogger;
use hexa_package_telegram\DTOs\TelegramDeliveryResult;
use Illuminate\Support\Str;

class TelegramTwoFactorTransport implements TelegramTwoFactorTransportContract
{
    public function __construct(
        protected TelegramConfigRepository $config,
        protected TelegramPushContract $push,
        protected TelegramDeliveryLogger $logger,
    ) {}

    public function isReady(): bool
    {
        return $this->config->isConfigured();
    }

    public function getBotUsername(): string
    {
        return $this->config->getBotUsername();
    }

    public function beginLink(User $user): array
    {
        if (!$this->isReady()) {
            return [
                'success' => false,
                'error'   => 'Telegram bot is not configured. Set it up in Settings > Telegram.',
            ];
        }

        $linkToken = Str::random(32);
        cache()->put('telegram_link:' . $linkToken, $user->getKey(), now()->addMinutes(10));

        return [
            'success'  => true,
            'bot_link' => 'https://t.me/' . $this->getBotUsername() . '?start=' . $linkToken,
            'token'    => $linkToken,
        ];
    }

    public function isLinked(User $user): bool
    {
        return trim((string) $user->telegram_chat_id) !== '';
    }

    public function sendChallengeCode(User $user, string $code): TelegramDeliveryResult
    {
        if (!$this->isLinked($user)) {
            return TelegramDeliveryResult::failure('Telegram is not linked for this user.');
        }

        return $this->push->sendTextToChatId(
            (string) $user->telegram_chat_id,
            'Your ' . config('hws.app_name', 'HWS') . ' 2FA code: ' . $code . "\n\nThis code expires in 10 minutes."
        );
    }

    public function handleWebhook(array $payload): void
    {
        $message = $payload['message'] ?? null;
        if (!is_array($message) || !isset($message['text'])) {
            return;
        }

        $chatId = isset($message['chat']['id']) ? (string) $message['chat']['id'] : '';
        $text = trim((string) ($message['text'] ?? ''));
        $username = (string) ($message['from']['username'] ?? ($message['from']['first_name'] ?? 'Unknown'));

        if ($chatId === '') {
            return;
        }

        if (str_starts_with($text, '/start ')) {
            $this->handleStartLink($chatId, $username, trim(substr($text, 7)));
            return;
        }

        if (preg_match('/^\d{6}$/', $text)) {
            $this->push->sendTextToChatId(
                $chatId,
                'Enter verification codes in the browser challenge screen. Telegram delivers codes, but it does not verify replies here.'
            );
            return;
        }

        $this->logger->logWebhook('ignored_message', [
            'chat_id' => $chatId,
            'text'    => $text,
        ]);
    }

    protected function handleStartLink(string $chatId, string $username, string $linkToken): void
    {
        $userId = cache()->pull('telegram_link:' . $linkToken);
        if (!$userId) {
            $this->push->sendTextToChatId(
                $chatId,
                'Invalid or expired link token. Start the Telegram setup again from ' . config('hws.app_name', 'the system') . '.'
            );
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->push->sendTextToChatId($chatId, 'The linked user account could not be found.');
            return;
        }

        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_username'=> $username,
        ]);

        $this->logger->logWebhook('linked_user', [
            'user_id'  => $user->getKey(),
            'chat_id'  => $chatId,
            'username' => $username,
        ]);

        $this->push->sendTextToChatId(
            $chatId,
            'Linked successfully to ' . $user->email . '. Return to the browser to finish enabling Telegram 2FA.'
        );
    }
}
