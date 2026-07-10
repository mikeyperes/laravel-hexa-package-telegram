<?php

namespace hexa_package_telegram\Domains\Bot;

use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\Webhooks\TelegramWebhookSecretService;
use hexa_package_telegram\DTOs\TelegramDeliveryResult;
use Illuminate\Support\Facades\Http;

class TelegramBotClient
{
    public function __construct(
        protected TelegramConfigRepository $config,
        protected TelegramWebhookSecretService $webhookSecrets,
    ) {}

    public function testBotToken(?string $token = null, ?string $botKey = null): TelegramDeliveryResult
    {
        $botToken = $token ?: $this->config->getBotToken($botKey);
        if (!$botToken) {
            return TelegramDeliveryResult::failure("No Telegram bot token configured.");
        }

        try {
            $response = Http::timeout(10)->get($this->buildUrl("getMe", $botToken));
            $data = $response->json() ?: [];

            if ($response->successful() && ($data["ok"] ?? false)) {
                $bot = $data["result"] ?? [];
                return TelegramDeliveryResult::success(
                    "Bot connected: @" . ($bot["username"] ?? "unknown") . " (" . ($bot["first_name"] ?? "Telegram Bot") . ").",
                    $bot
                );
            }

            return TelegramDeliveryResult::failure("Invalid bot token.", $data ?: null);
        } catch (\Throwable $e) {
            return TelegramDeliveryResult::failure("Error: " . $e->getMessage());
        }
    }

    public function setWebhook(?string $url = null, ?string $botKey = null): TelegramDeliveryResult
    {
        $botToken = $this->config->getBotToken($botKey);
        if (!$botToken) {
            return TelegramDeliveryResult::failure("No Telegram bot token configured.");
        }

        $targetUrl = $url ?: route("telegram.webhook");
        $targetScheme = strtolower((string) parse_url($targetUrl, PHP_URL_SCHEME));
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL) || $targetScheme !== 'https') {
            return TelegramDeliveryResult::failure('Telegram webhook URLs must be valid HTTPS URLs.');
        }

        $resolvedBotKey = (string) ($botKey ?: $this->config->getActiveBotKey());
        $secretToken = $this->webhookSecrets->forBot($resolvedBotKey);

        try {
            $response = Http::timeout(15)->post($this->buildUrl("setWebhook", $botToken), [
                "url" => $targetUrl,
                "secret_token" => $secretToken,
            ]);
            $data = $response->json() ?: [];

            if ($response->successful() && ($data["ok"] ?? false)) {
                return TelegramDeliveryResult::success("Webhook set successfully to " . $targetUrl, ["result" => $data["result"] ?? null, "url" => $targetUrl]);
            }

            return TelegramDeliveryResult::failure("Failed to set webhook: " . ($data["description"] ?? "Unknown error"), $data ?: null);
        } catch (\Throwable $e) {
            return TelegramDeliveryResult::failure("Error: " . $e->getMessage());
        }
    }

    public function getWebhookInfo(?string $botKey = null): array
    {
        $botToken = $this->config->getBotToken($botKey);
        if (!$botToken) {
            return ["url" => "", "pending_update_count" => 0];
        }

        try {
            $response = Http::timeout(10)->get($this->buildUrl("getWebhookInfo", $botToken));
            $data = $response->json() ?: [];

            if ($response->successful() && ($data["ok"] ?? false)) {
                return $data["result"] ?? [];
            }
        } catch (\Throwable) {
        }

        return ["url" => "", "pending_update_count" => 0];
    }

    public function sendText(string $chatId, string $message, array $options = []): TelegramDeliveryResult
    {
        $botKey = $options["bot_key"] ?? null;
        unset($options["bot_key"]);

        return $this->sendPayload($chatId, array_merge([
            "text" => $message,
            "parse_mode" => $options["parse_mode"] ?? "HTML",
            "disable_web_page_preview" => $options["disable_web_page_preview"] ?? true,
        ], $options), $botKey);
    }

    public function sendRichMessage(string $chatId, string $message, array $buttons, array $options = []): TelegramDeliveryResult
    {
        $botKey = $options["bot_key"] ?? null;
        unset($options["bot_key"]);

        return $this->sendPayload($chatId, array_merge([
            "text" => $message,
            "parse_mode" => $options["parse_mode"] ?? "HTML",
            "reply_markup" => json_encode(["inline_keyboard" => $buttons]),
        ], $options), $botKey);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $message = "", array $options = []): TelegramDeliveryResult
    {
        $botKey = $options["bot_key"] ?? null;
        unset($options["bot_key"]);

        $botToken = $this->config->getBotToken($botKey);
        if (!$botToken) {
            return TelegramDeliveryResult::failure("No Telegram bot token configured.");
        }

        try {
            $payload = array_merge([
                "callback_query_id" => $callbackQueryId,
                "text" => $message,
                "show_alert" => false,
            ], $options);
            $response = Http::timeout(10)->post($this->buildUrl("answerCallbackQuery", $botToken), $payload);
            $data = $response->json() ?: [];

            if ($response->successful() && ($data["ok"] ?? false)) {
                return TelegramDeliveryResult::success("Callback answered.", $data["result"] ?? null);
            }

            return TelegramDeliveryResult::failure("Telegram callback error: " . ($data["description"] ?? "Unknown"), $data ?: null);
        } catch (\Throwable $e) {
            return TelegramDeliveryResult::failure("Error: " . $e->getMessage());
        }
    }

    protected function sendPayload(string $chatId, array $payload, ?string $botKey = null): TelegramDeliveryResult
    {
        $botToken = $this->config->getBotToken($botKey);
        if (!$botToken) {
            return TelegramDeliveryResult::failure("No Telegram bot token configured.");
        }

        try {
            $response = Http::timeout(15)->post($this->buildUrl("sendMessage", $botToken), array_merge($payload, ["chat_id" => $chatId]));
            $data = $response->json() ?: [];

            if ($response->successful() && ($data["ok"] ?? false)) {
                return TelegramDeliveryResult::success("Message sent.", [
                    "message_id" => $data["result"]["message_id"] ?? null,
                    "chat_id" => $chatId,
                ]);
            }

            return TelegramDeliveryResult::failure("Telegram error: " . ($data["description"] ?? "Unknown"), $data ?: null);
        } catch (\Throwable $e) {
            return TelegramDeliveryResult::failure("Error: " . $e->getMessage());
        }
    }

    protected function buildUrl(string $method, string $token): string
    {
        return "https://api.telegram.org/bot" . $token . "/" . $method;
    }
}
