<?php

namespace hexa_package_telegram\Domains\Config;

use hexa_core\Models\Setting;
use hexa_core\Services\CredentialService;
use Illuminate\Support\Facades\Crypt;

class TelegramConfigRepository
{
    public const CREDENTIAL_SLUG = 'telegram';
    public const BOT_TOKEN_KEY = 'bot_token';

    public function __construct(
        protected CredentialService $credentials,
    ) {}

    public function getBotToken(): ?string
    {
        $canonical = $this->credentials->get(self::CREDENTIAL_SLUG, self::BOT_TOKEN_KEY);
        if (!empty($canonical)) {
            return $canonical;
        }

        $legacyTwoFactor = Setting::getValue('2fa_telegram_bot_token', '');
        if (!empty($legacyTwoFactor)) {
            try {
                $decrypted = Crypt::decryptString($legacyTwoFactor);
                if ($decrypted !== '') {
                    return $decrypted;
                }
            } catch (\Throwable) {
                // Ignore malformed legacy values and continue probing.
            }
        }

        $legacyPlain = Setting::getValue('telegram_bot_token', '');
        if (!empty($legacyPlain)) {
            return $legacyPlain;
        }

        $envValue = (string) config('hws.telegram.bot_token', '');
        return $envValue !== '' ? $envValue : null;
    }

    public function getBotUsername(): string
    {
        $username = (string) (
            Setting::getValue('telegram_bot_username', '')
            ?: Setting::getValue('2fa_telegram_bot_username', '')
            ?: config('hws.telegram.bot_username', '')
        );

        return trim($username, "@ \t\n\r\0\x0B");
    }

    public function getDefaultChatId(): ?string
    {
        $chatId = (string) (
            Setting::getValue('telegram_default_chat_id', '')
            ?: Setting::getValue('telegram_chat_id', '')
        );

        $chatId = trim($chatId);
        return $chatId !== '' ? $chatId : null;
    }

    public function credentialExists(): bool
    {
        if ($this->credentials->exists(self::CREDENTIAL_SLUG, self::BOT_TOKEN_KEY)) {
            return true;
        }

        return !empty(Setting::getValue('2fa_telegram_bot_token', ''))
            || !empty(Setting::getValue('telegram_bot_token', ''))
            || !empty(config('hws.telegram.bot_token', ''));
    }

    public function getMaskedBotToken(): string
    {
        $canonical = $this->credentials->getMasked(self::CREDENTIAL_SLUG, self::BOT_TOKEN_KEY);
        if ($canonical !== '') {
            return $canonical;
        }

        return $this->credentials->mask($this->getBotToken());
    }

    public function isConfigured(): bool
    {
        return !empty($this->getBotToken()) && $this->getBotUsername() !== '';
    }

    /**
     * @return array{configured: bool, bot_username: string, default_chat_id: string|null, masked_bot_token: string, webhook_url: string}
     */
    public function getStatus(): array
    {
        return [
            'configured'      => $this->isConfigured(),
            'bot_username'    => $this->getBotUsername(),
            'default_chat_id' => $this->getDefaultChatId(),
            'masked_bot_token'=> $this->getMaskedBotToken(),
            'webhook_url'     => route('telegram.webhook'),
        ];
    }

    public function saveSettings(string $botUsername, string $defaultChatId = ''): void
    {
        $normalizedUsername = trim($botUsername, "@ \t\n\r\0\x0B");
        $normalizedChatId = trim($defaultChatId);

        Setting::setValue('telegram_bot_username', $normalizedUsername, 'integrations');
        Setting::setValue('2fa_telegram_bot_username', $normalizedUsername, 'security');
        Setting::setValue('telegram_default_chat_id', $normalizedChatId, 'integrations');
        Setting::setValue('telegram_chat_id', $normalizedChatId, 'integrations');
    }

    public function storeBotToken(string $token): void
    {
        $trimmed = trim($token);
        if ($trimmed === '') {
            return;
        }

        $this->credentials->store(self::CREDENTIAL_SLUG, self::BOT_TOKEN_KEY, $trimmed);
        Setting::setValue('2fa_telegram_bot_token', Crypt::encryptString($trimmed), 'security');
    }
}
