<?php

namespace hexa_package_telegram\Domains\Bot;

use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\DTOs\TelegramDeliveryResult;
use Illuminate\Support\Facades\Http;

class TelegramBotClient
{
    public function __construct(
        protected TelegramConfigRepository $config,
    ) {}

    public function testBotToken(?string $token = null): TelegramDeliveryResult
    {
        $botToken = $token ?: $this->config->getBotToken();
        if (!$botToken) {
            return TelegramDeliveryResult::failure('No Telegram bot token configured.');
        }

        try {
            $response = Http::timeout(10)->get($this->buildUrl('getMe', $botToken));
            $data = $response->json() ?: [];

            if ($response->successful() && ($data['ok'] ?? false)) {
                $bot = $data['result'] ?? [];
                return TelegramDeliveryResult::success(
                    'Bot connected: @' . ($bot['username'] ?? 'unknown') . ' (' . ($bot['first_name'] ?? 'Telegram Bot') . ').',
                    $bot
                );
            }

            return TelegramDeliveryResult::failure('Invalid bot token.', $data ?: null);
        } catch (\Throwable $e) {
            return TelegramDeliveryResult::failure('Error: ' . $e->getMessage());
        }
    }

    public function setWebhook(?string $url = null): TelegramDeliveryResult
    {
        $botToken = $this->config->getBotToken();
        if (!$botToken) {
            return TelegramDeliveryResult::failure('No Telegram bot token configured.');
        }

        $targetUrl = $url ?: route('telegram.webhook');

        try {
            $response = Http::timeout(15)->post($this->buildUrl('setWebhook', $botToken), [
                'url' => $targetUrl,
            ]);
            $data = $response->json() ?: [];

            if ($response->successful() && ($data['ok'] ?? false)) {
                return TelegramDeliveryResult::success('Webhook set successfully to ' . $targetUrl, $data['result'] ?? null);
            }

            return TelegramDeliveryResult::failure(
                'Failed to set webhook: ' . ($data['description'] ?? 'Unknown error'),
                $data ?: null
            );
        } catch (\Throwable $e) {
            return TelegramDeliveryResult::failure('Error: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getWebhookInfo(): array
    {
        $botToken = $this->config->getBotToken();
        if (!$botToken) {
            return ['url' => '', 'pending_update_count' => 0];
        }

        try {
            $response = Http::timeout(10)->get($this->buildUrl('getWebhookInfo', $botToken));
            $data = $response->json() ?: [];

            if ($response->successful() && ($data['ok'] ?? false)) {
                return $data['result'] ?? [];
            }
        } catch (\Throwable) {
            // Fall through to empty defaults.
        }

        return ['url' => '', 'pending_update_count' => 0];
    }

    public function sendText(string $chatId, string $message, array $options = []): TelegramDeliveryResult
    {
        return $this->sendPayload($chatId, array_merge([
            'text'                     => $message,
            'parse_mode'               => $options['parse_mode'] ?? 'HTML',
            'disable_web_page_preview' => $options['disable_web_page_preview'] ?? true,
        ], $options));
    }

    public function sendRichMessage(string $chatId, string $message, array $buttons, array $options = []): TelegramDeliveryResult
    {
        $payload = array_merge([
            'text'         => $message,
            'parse_mode'   => $options['parse_mode'] ?? 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $buttons]),
        ], $options);

        return $this->sendPayload($chatId, $payload);
    }

    protected function sendPayload(string $chatId, array $payload): TelegramDeliveryResult
    {
        $botToken = $this->config->getBotToken();
        if (!$botToken) {
            return TelegramDeliveryResult::failure('No Telegram bot token configured.');
        }

        try {
            $response = Http::timeout(15)->post($this->buildUrl('sendMessage', $botToken), array_merge($payload, [
                'chat_id' => $chatId,
            ]));
            $data = $response->json() ?: [];

            if ($response->successful() && ($data['ok'] ?? false)) {
                return TelegramDeliveryResult::success('Message sent.', [
                    'message_id' => $data['result']['message_id'] ?? null,
                    'chat_id'    => $chatId,
                ]);
            }

            return TelegramDeliveryResult::failure(
                'Telegram error: ' . ($data['description'] ?? 'Unknown'),
                $data ?: null
            );
        } catch (\Throwable $e) {
            return TelegramDeliveryResult::failure('Error: ' . $e->getMessage());
        }
    }

    protected function buildUrl(string $method, string $token): string
    {
        return 'https://api.telegram.org/bot' . $token . '/' . $method;
    }
}
