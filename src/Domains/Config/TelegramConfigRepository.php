<?php

namespace hexa_package_telegram\Domains\Config;

use hexa_core\Models\Setting;
use hexa_core\Services\CredentialService;
use Illuminate\Support\Facades\Crypt;

class TelegramConfigRepository
{
    public const CREDENTIAL_SLUG = "telegram";
    public const BOT_TOKEN_KEY = "bot_token";
    public const DEFAULT_BOT_KEY = "default";
    public const BOTS_SETTING_KEY = "telegram_bot_registry";
    public const ACTIVE_BOT_SETTING_KEY = "telegram_active_bot_key";
    public const RECENT_CHATS_SETTING_KEY = "telegram_recent_chats";

    public function __construct(protected CredentialService $credentials) {}

    public function getBotToken(?string $botKey = null): ?string
    {
        $key = $this->botKey($botKey);
        $token = $this->credentials->get(self::CREDENTIAL_SLUG, $this->credentialKeyForBot($key));
        if (!empty($token)) {
            return $token;
        }

        if ($key !== self::DEFAULT_BOT_KEY) {
            return null;
        }

        $legacyEncrypted = Setting::getValue("2fa_telegram_bot_token", "");
        if (!empty($legacyEncrypted)) {
            try {
                $decrypted = Crypt::decryptString($legacyEncrypted);
                if ($decrypted !== "") {
                    return $decrypted;
                }
            } catch (\Throwable) {
            }
        }

        $legacyPlain = Setting::getValue("telegram_bot_token", "");
        if (!empty($legacyPlain)) {
            return $legacyPlain;
        }

        $env = (string) config("hws.telegram.bot_token", "");
        return $env !== "" ? $env : null;
    }

    public function getBotUsername(?string $botKey = null): string
    {
        $key = $this->botKey($botKey);
        $bot = $this->rawBots()[$key] ?? [];
        $username = (string) ($bot["bot_username"] ?? "");

        if ($username === "" && $key === self::DEFAULT_BOT_KEY) {
            $username = (string) (Setting::getValue("telegram_bot_username", "")
                ?: Setting::getValue("2fa_telegram_bot_username", "")
                ?: config("hws.telegram.bot_username", ""));
        }

        return trim($username, "@ \t\n\r");
    }

    public function getDefaultChatId(?string $botKey = null): ?string
    {
        $key = $this->botKey($botKey);
        $bot = $this->rawBots()[$key] ?? [];
        $chatId = (string) ($bot["default_chat_id"] ?? "");

        if ($chatId === "" && $key === self::DEFAULT_BOT_KEY) {
            $chatId = (string) (Setting::getValue("telegram_default_chat_id", "") ?: Setting::getValue("telegram_chat_id", ""));
        }

        $chatId = trim($chatId);
        return $chatId !== "" ? $chatId : null;
    }

    public function credentialExists(?string $botKey = null): bool
    {
        $key = $this->botKey($botKey);
        if ($this->credentials->exists(self::CREDENTIAL_SLUG, $this->credentialKeyForBot($key))) {
            return true;
        }

        return $key === self::DEFAULT_BOT_KEY && $this->legacyCredentialExists();
    }

    public function getMaskedBotToken(?string $botKey = null): string
    {
        $key = $this->botKey($botKey);
        $masked = $this->credentials->getMasked(self::CREDENTIAL_SLUG, $this->credentialKeyForBot($key));
        if ($masked !== "") {
            return $masked;
        }

        return $key === self::DEFAULT_BOT_KEY ? $this->credentials->mask($this->getBotToken($key)) : "";
    }

    public function isConfigured(?string $botKey = null): bool
    {
        $key = $this->botKey($botKey);
        return !empty($this->getBotToken($key)) && $this->getBotUsername($key) !== "";
    }

    public function getStatus(): array
    {
        $activeKey = $this->getActiveBotKey();

        return [
            "configured" => $this->isConfigured($activeKey),
            "bot_username" => $this->getBotUsername($activeKey),
            "default_chat_id" => $this->getDefaultChatId($activeKey),
            "masked_bot_token" => $this->getMaskedBotToken($activeKey),
            "webhook_url" => route("telegram.webhook"),
            "active_bot_key" => $activeKey,
            "active_bot" => $this->findBot($activeKey),
            "bots" => $this->getBots(),
            "recent_chats" => $this->getRecentChats($activeKey),
        ];
    }

    public function saveSettings(string $botUsername, string $defaultChatId = ""): void
    {
        $key = $this->getActiveBotKey();
        $current = $this->rawBots()[$key] ?? [];
        $this->saveBot([
            "key" => $key,
            "name" => $current["name"] ?? "Default Telegram Bot",
            "purpose" => $current["purpose"] ?? "",
            "bot_username" => $botUsername,
            "default_chat_id" => $defaultChatId,
        ], $key);
        $this->syncLegacyAliases($key);
    }

    public function storeBotToken(string $token, ?string $botKey = null): void
    {
        $token = trim($token);
        if ($token === "") {
            return;
        }

        $key = $this->botKey($botKey);
        $this->credentials->store(self::CREDENTIAL_SLUG, $this->credentialKeyForBot($key), $token);

        if ($key === self::DEFAULT_BOT_KEY) {
            Setting::setValue("2fa_telegram_bot_token", Crypt::encryptString($token), "security");
        }
    }

    public function getBots(): array
    {
        $active = $this->getActiveBotKey();
        $bots = [];

        foreach ($this->rawBots() as $key => $bot) {
            $bots[] = $this->decorateBot($bot, $active);
        }

        return $bots;
    }

    public function getActiveBotKey(): string
    {
        $active = $this->sanitizeBotKey((string) Setting::getValue(self::ACTIVE_BOT_SETTING_KEY, ""));
        $bots = $this->rawBots(false);

        if ($active !== "" && isset($bots[$active])) {
            return $active;
        }

        if (isset($bots[self::DEFAULT_BOT_KEY])) {
            return self::DEFAULT_BOT_KEY;
        }

        return array_key_first($bots) ?: self::DEFAULT_BOT_KEY;
    }

    public function findBot(?string $botKey): ?array
    {
        $key = $this->sanitizeBotKey((string) $botKey);
        $bots = $this->rawBots();

        if ($key === "" || !isset($bots[$key])) {
            return null;
        }

        return $this->decorateBot($bots[$key], $this->getActiveBotKey());
    }

    public function saveBot(array $input, ?string $currentKey = null): array
    {
        $bots = $this->rawBots(false);
        $key = $this->sanitizeBotKey((string) ($currentKey ?: ($input["key"] ?? "")));

        if ($key === "") {
            $key = $this->sanitizeBotKey((string) ($input["name"] ?? "telegram_bot")) ?: "telegram_bot";
        }

        $existing = $bots[$key] ?? [];
        $bots[$key] = $this->normalizeBot([
            "key" => $key,
            "name" => $input["name"] ?? $existing["name"] ?? $this->humanName($key),
            "purpose" => $input["purpose"] ?? $existing["purpose"] ?? "",
            "bot_username" => $input["bot_username"] ?? $existing["bot_username"] ?? "",
            "default_chat_id" => $input["default_chat_id"] ?? $existing["default_chat_id"] ?? "",
            "enabled" => $input["enabled"] ?? $existing["enabled"] ?? true,
        ], $key);

        $this->persistBots($bots);

        if (Setting::getValue(self::ACTIVE_BOT_SETTING_KEY, "") === "") {
            $this->setActiveBot($key);
        }

        return $this->decorateBot($bots[$key], $this->getActiveBotKey());
    }

    public function setActiveBot(string $botKey): array
    {
        $key = $this->sanitizeBotKey($botKey);
        $bots = $this->rawBots();

        if (!isset($bots[$key])) {
            throw new \InvalidArgumentException("Telegram bot does not exist.");
        }

        Setting::setValue(self::ACTIVE_BOT_SETTING_KEY, $key, "integrations");
        $this->syncLegacyAliases($key);

        return $this->decorateBot($bots[$key], $key);
    }

    public function getRecentChats(?string $botKey = null): array
    {
        $key = $this->botKey($botKey);
        $decoded = json_decode((string) Setting::getValue(self::RECENT_CHATS_SETTING_KEY, ""), true);
        if (!is_array($decoded)) {
            return [];
        }

        $rows = [];
        foreach ($decoded as $chat) {
            if (!is_array($chat) || (string) ($chat["bot_key"] ?? "") !== $key) {
                continue;
            }

            $rows[] = [
                "bot_key" => $key,
                "id" => (string) ($chat["id"] ?? ""),
                "type" => (string) ($chat["type"] ?? ""),
                "title" => (string) ($chat["title"] ?? ""),
                "username" => (string) ($chat["username"] ?? ""),
                "first_name" => (string) ($chat["first_name"] ?? ""),
                "last_name" => (string) ($chat["last_name"] ?? ""),
                "label" => (string) ($chat["label"] ?? ""),
                "last_seen_at" => (string) ($chat["last_seen_at"] ?? ""),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp((string) ($b["last_seen_at"] ?? ""), (string) ($a["last_seen_at"] ?? "")));

        return array_values(array_slice($rows, 0, 20));
    }

    public function rememberInboundChat(array $chat, ?string $botKey = null): ?array
    {
        $id = trim((string) ($chat["id"] ?? ""));
        if ($id === "") {
            return null;
        }

        $key = $this->botKey($botKey);
        $record = [
            "bot_key" => $key,
            "id" => $id,
            "type" => trim((string) ($chat["type"] ?? "")),
            "title" => trim((string) ($chat["title"] ?? "")),
            "username" => trim((string) ($chat["username"] ?? ""), "@ \t\n\r"),
            "first_name" => trim((string) ($chat["first_name"] ?? "")),
            "last_name" => trim((string) ($chat["last_name"] ?? "")),
            "label" => $this->chatLabel($chat),
            "last_seen_at" => now()->toIso8601String(),
        ];

        $decoded = json_decode((string) Setting::getValue(self::RECENT_CHATS_SETTING_KEY, ""), true);
        $rows = is_array($decoded) ? $decoded : [];
        $rows = array_values(array_filter($rows, static function ($row) use ($key, $id): bool {
            return !is_array($row) || (string) ($row["bot_key"] ?? "") !== $key || (string) ($row["id"] ?? "") !== $id;
        }));
        array_unshift($rows, $record);

        Setting::setValue(self::RECENT_CHATS_SETTING_KEY, json_encode(array_slice($rows, 0, 50), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "integrations");

        return $record;
    }

    public function credentialKeyForBot(?string $botKey): string
    {
        $key = $this->sanitizeBotKey((string) ($botKey ?: self::DEFAULT_BOT_KEY));
        return $key === self::DEFAULT_BOT_KEY ? self::BOT_TOKEN_KEY : self::BOT_TOKEN_KEY . "_" . $key;
    }

    protected function botKey(?string $botKey): string
    {
        $key = $this->sanitizeBotKey((string) $botKey);
        return $key !== "" ? $key : $this->getActiveBotKey();
    }

    protected function rawBots(bool $includeLegacy = true): array
    {
        $decoded = json_decode((string) Setting::getValue(self::BOTS_SETTING_KEY, ""), true);
        $bots = [];

        if (is_array($decoded)) {
            foreach ($decoded as $index => $bot) {
                if (!is_array($bot)) {
                    continue;
                }
                $fallback = is_string($index) ? $index : (string) ($bot["key"] ?? "");
                $normalized = $this->normalizeBot($bot, $fallback);
                $bots[$normalized["key"]] = $normalized;
            }
        }

        if ($includeLegacy && (!isset($bots[self::DEFAULT_BOT_KEY]) || $this->legacyConfigExists())) {
            $bots[self::DEFAULT_BOT_KEY] = array_merge($this->legacyBot(), $bots[self::DEFAULT_BOT_KEY] ?? ["key" => self::DEFAULT_BOT_KEY]);
            $bots[self::DEFAULT_BOT_KEY]["key"] = self::DEFAULT_BOT_KEY;
        }

        if (empty($bots)) {
            $bots[self::DEFAULT_BOT_KEY] = $this->legacyBot();
        }

        return $bots;
    }

    protected function persistBots(array $bots): void
    {
        $payload = [];
        foreach ($bots as $key => $bot) {
            $payload[] = $this->normalizeBot($bot, (string) $key);
        }

        Setting::setValue(self::BOTS_SETTING_KEY, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "integrations");
    }

    protected function normalizeBot(array $bot, string $fallbackKey): array
    {
        $key = $this->sanitizeBotKey((string) ($bot["key"] ?? $fallbackKey)) ?: self::DEFAULT_BOT_KEY;

        return [
            "key" => $key,
            "name" => trim((string) ($bot["name"] ?? "")) ?: $this->humanName($key),
            "purpose" => trim((string) ($bot["purpose"] ?? "")),
            "bot_username" => trim((string) ($bot["bot_username"] ?? ""), "@ \t\n\r"),
            "default_chat_id" => trim((string) ($bot["default_chat_id"] ?? "")),
            "enabled" => filter_var($bot["enabled"] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
        ];
    }

    protected function decorateBot(array $bot, string $activeKey): array
    {
        $key = (string) $bot["key"];

        return array_merge($bot, [
            "bot_username" => $this->getBotUsername($key),
            "default_chat_id" => $this->getDefaultChatId($key),
            "token_key" => $this->credentialKeyForBot($key),
            "masked_bot_token" => $this->getMaskedBotToken($key),
            "configured" => $this->isConfigured($key),
            "is_active" => $key === $activeKey,
            "webhook_url" => route("telegram.webhook"),
        ]);
    }

    protected function legacyBot(): array
    {
        return $this->normalizeBot([
            "key" => self::DEFAULT_BOT_KEY,
            "name" => "Default Telegram Bot",
            "purpose" => "Legacy/default package bot used when no feature-specific bot is selected.",
            "bot_username" => Setting::getValue("telegram_bot_username", "") ?: Setting::getValue("2fa_telegram_bot_username", "") ?: config("hws.telegram.bot_username", ""),
            "default_chat_id" => Setting::getValue("telegram_default_chat_id", "") ?: Setting::getValue("telegram_chat_id", ""),
            "enabled" => true,
        ], self::DEFAULT_BOT_KEY);
    }

    protected function legacyCredentialExists(): bool
    {
        return !empty(Setting::getValue("2fa_telegram_bot_token", ""))
            || !empty(Setting::getValue("telegram_bot_token", ""))
            || !empty(config("hws.telegram.bot_token", ""));
    }

    protected function legacyConfigExists(): bool
    {
        return $this->legacyCredentialExists()
            || trim((string) Setting::getValue("telegram_bot_username", "")) !== ""
            || trim((string) Setting::getValue("2fa_telegram_bot_username", "")) !== ""
            || trim((string) Setting::getValue("telegram_default_chat_id", "")) !== ""
            || trim((string) Setting::getValue("telegram_chat_id", "")) !== "";
    }

    protected function syncLegacyAliases(string $botKey): void
    {
        $bot = $this->rawBots()[$botKey] ?? $this->legacyBot();

        Setting::setValue("telegram_bot_username", trim((string) ($bot["bot_username"] ?? ""), "@ \t\n\r"), "integrations");
        Setting::setValue("2fa_telegram_bot_username", trim((string) ($bot["bot_username"] ?? ""), "@ \t\n\r"), "security");
        Setting::setValue("telegram_default_chat_id", trim((string) ($bot["default_chat_id"] ?? "")), "integrations");
        Setting::setValue("telegram_chat_id", trim((string) ($bot["default_chat_id"] ?? "")), "integrations");
    }

    protected function sanitizeBotKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace("/[^a-z0-9]+/", "_", $key) ?: "";
        return substr(trim($key, "_"), 0, 48);
    }

    protected function chatLabel(array $chat): string
    {
        $title = trim((string) ($chat["title"] ?? ""));
        if ($title !== "") {
            return $title;
        }

        $name = trim(trim((string) ($chat["first_name"] ?? "")) . " " . trim((string) ($chat["last_name"] ?? "")));
        if ($name !== "") {
            return $name;
        }

        $username = trim((string) ($chat["username"] ?? ""), "@ \t\n\r");
        return $username !== "" ? "@" . $username : (string) ($chat["id"] ?? "Telegram chat");
    }

    protected function humanName(string $key): string
    {
        $name = trim(str_replace("_", " ", $key));
        return $name === "" ? "Telegram Bot" : ucwords($name);
    }
}
