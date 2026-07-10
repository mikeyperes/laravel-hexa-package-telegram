<?php

namespace hexa_package_telegram\Domains\Webhooks;

use hexa_core\Services\CredentialService;

class TelegramWebhookSecretService
{
    private const CREDENTIAL_SLUG = 'telegram';

    public function __construct(private CredentialService $credentials)
    {
    }

    public function forBot(string $botKey): string
    {
        $credentialKey = $this->credentialKey($botKey);
        $secret = trim((string) $this->credentials->get(self::CREDENTIAL_SLUG, $credentialKey));
        if ($secret !== '') {
            return $secret;
        }

        $secret = bin2hex(random_bytes(32));
        $this->credentials->store(self::CREDENTIAL_SLUG, $credentialKey, $secret);

        return $secret;
    }

    /**
     * @param array<int, string> $botKeys
     */
    public function resolveBotKey(array $botKeys, string $providedSecret): ?string
    {
        if ($providedSecret === '') {
            return null;
        }

        foreach (array_values(array_unique(array_map([$this, 'normalizeBotKey'], $botKeys))) as $botKey) {
            $expected = trim((string) $this->credentials->get(self::CREDENTIAL_SLUG, $this->credentialKey($botKey)));
            if ($expected !== '' && hash_equals($expected, $providedSecret)) {
                return $botKey;
            }
        }

        return null;
    }

    private function credentialKey(string $botKey): string
    {
        $botKey = $this->normalizeBotKey($botKey);

        return $botKey === 'default' ? 'webhook_secret' : 'webhook_secret_' . $botKey;
    }

    private function normalizeBotKey(string $botKey): string
    {
        $botKey = strtolower(trim($botKey));
        $botKey = preg_replace('/[^a-z0-9]+/', '_', $botKey) ?: '';

        return substr(trim($botKey, '_'), 0, 48) ?: 'default';
    }
}
