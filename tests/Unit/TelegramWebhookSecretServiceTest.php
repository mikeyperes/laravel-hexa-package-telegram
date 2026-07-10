<?php

namespace HexaPackageTests\Telegram;

use hexa_core\Services\CredentialService;
use hexa_package_telegram\Domains\Webhooks\TelegramWebhookSecretService;
use PHPUnit\Framework\TestCase;

class TelegramWebhookSecretServiceTest extends TestCase
{
    public function test_it_generates_and_stores_a_provider_compatible_secret(): void
    {
        $credentials = $this->createMock(CredentialService::class);
        $credentials->expects($this->once())->method('get')->with('telegram', 'webhook_secret_news_bot')->willReturn(null);
        $credentials->expects($this->once())->method('store')->with(
            'telegram',
            'webhook_secret_news_bot',
            $this->callback(static fn (string $secret): bool => preg_match('/^[a-f0-9]{64}$/', $secret) === 1)
        );

        $secret = (new TelegramWebhookSecretService($credentials))->forBot('News Bot');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $secret);
    }

    public function test_it_resolves_the_bot_only_from_a_matching_stored_secret(): void
    {
        $credentials = $this->createStub(CredentialService::class);
        $credentials->method('get')->willReturnCallback(static fn (string $slug, string $key): ?string => match ($key) {
            'webhook_secret' => 'default-secret',
            'webhook_secret_news' => 'news-secret',
            default => null,
        });
        $service = new TelegramWebhookSecretService($credentials);

        $this->assertSame('news', $service->resolveBotKey(['default', 'news'], 'news-secret'));
        $this->assertNull($service->resolveBotKey(['default', 'news'], 'invalid'));
        $this->assertNull($service->resolveBotKey(['default', 'news'], ''));
    }
}
