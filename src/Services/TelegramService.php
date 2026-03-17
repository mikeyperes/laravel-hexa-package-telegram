<?php

namespace hexa_package_telegram\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use hexa_core\Models\Setting;

class TelegramService
{
    /**
     * @return string|null
     */
    private function getBotToken(): ?string
    {
        return Setting::getValue('telegram_bot_token');
    }

    /**
     * @return string|null
     */
    private function getDefaultChatId(): ?string
    {
        return Setting::getValue('telegram_chat_id');
    }

    /**
     * Test the bot token by calling getMe.
     *
     * @param string|null $token Override token to test.
     * @return array{success: bool, message: string}
     */
    public function testBotToken(?string $token = null): array
    {
        $tk = $token ?? $this->getBotToken();
        if (!$tk) {
            return ['success' => false, 'message' => 'No Telegram bot token configured.'];
        }

        try {
            $response = Http::timeout(10)
                ->get("https://api.telegram.org/bot{$tk}/getMe");

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ok'] ?? false) {
                    $bot = $data['result'];
                    return ['success' => true, 'message' => "Bot connected: @{$bot['username']} ({$bot['first_name']})."];
                }
            }
            return ['success' => false, 'message' => 'Invalid bot token.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Send a text message.
     *
     * @param string $message Message text (supports HTML).
     * @param string|null $chatId Target chat ID. Uses default if null.
     * @return array{success: bool, message: string, data: array|null}
     */
    public function sendMessage(string $message, ?string $chatId = null): array
    {
        $token = $this->getBotToken();
        if (!$token) {
            return ['success' => false, 'message' => 'No Telegram bot token configured.', 'data' => null];
        }

        $chat = $chatId ?? $this->getDefaultChatId();
        if (!$chat) {
            return ['success' => false, 'message' => 'No Telegram chat ID configured.', 'data' => null];
        }

        try {
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chat,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ok'] ?? false) {
                    return [
                        'success' => true,
                        'message' => 'Message sent.',
                        'data' => ['message_id' => $data['result']['message_id'] ?? null],
                    ];
                }
            }

            $error = $response->json();
            return ['success' => false, 'message' => 'Telegram error: ' . ($error['description'] ?? 'Unknown'), 'data' => null];
        } catch (\Exception $e) {
            Log::error('TelegramService::sendMessage error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Send a message with inline keyboard buttons.
     *
     * @param string $message Message text.
     * @param array $buttons Array of rows, each row is array of ['text' => '...', 'callback_data' => '...'].
     * @param string|null $chatId Target chat ID.
     * @return array{success: bool, message: string, data: array|null}
     */
    public function sendMessageWithButtons(string $message, array $buttons, ?string $chatId = null): array
    {
        $token = $this->getBotToken();
        if (!$token) {
            return ['success' => false, 'message' => 'No Telegram bot token configured.', 'data' => null];
        }

        $chat = $chatId ?? $this->getDefaultChatId();
        if (!$chat) {
            return ['success' => false, 'message' => 'No Telegram chat ID configured.', 'data' => null];
        }

        try {
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chat,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode(['inline_keyboard' => $buttons]),
                ]);

            if ($response->successful() && ($response->json()['ok'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'Message with buttons sent.',
                    'data' => ['message_id' => $response->json()['result']['message_id'] ?? null],
                ];
            }

            $error = $response->json();
            return ['success' => false, 'message' => 'Telegram error: ' . ($error['description'] ?? 'Unknown'), 'data' => null];
        } catch (\Exception $e) {
            Log::error('TelegramService::sendMessageWithButtons error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Send article approval request via Telegram.
     *
     * @param int $articleId
     * @param string $title
     * @param string $siteName
     * @param int $wordCount
     * @param string|null $chatId
     * @return array{success: bool, message: string, data: array|null}
     */
    public function sendArticleApproval(int $articleId, string $title, string $siteName, int $wordCount, ?string $chatId = null): array
    {
        $message = "<b>Article Ready for Review</b>\n\n"
            . "<b>Title:</b> {$title}\n"
            . "<b>Site:</b> {$siteName}\n"
            . "<b>Words:</b> {$wordCount}\n"
            . "<b>ID:</b> {$articleId}";

        $buttons = [
            [
                ['text' => 'Approve & Publish', 'callback_data' => "publish:{$articleId}"],
                ['text' => 'Redraft', 'callback_data' => "redraft:{$articleId}"],
            ],
            [
                ['text' => 'Re-pick Photos', 'callback_data' => "rephotos:{$articleId}"],
                ['text' => 'View Article', 'callback_data' => "view:{$articleId}"],
            ],
        ];

        return $this->sendMessageWithButtons($message, $buttons, $chatId);
    }

    /**
     * Send a notification that an article was published.
     *
     * @param string $title
     * @param string $siteName
     * @param string|null $wpUrl
     * @param string|null $chatId
     * @return array{success: bool, message: string, data: array|null}
     */
    public function notifyPublished(string $title, string $siteName, ?string $wpUrl = null, ?string $chatId = null): array
    {
        $message = "Published: <b>{$title}</b>\n"
            . "Site: {$siteName}";

        if ($wpUrl) {
            $message .= "\n<a href=\"{$wpUrl}\">View Post</a>";
        }

        return $this->sendMessage($message, $chatId);
    }
}
