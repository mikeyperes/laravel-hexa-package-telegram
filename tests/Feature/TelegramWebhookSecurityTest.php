<?php

namespace HexaPackageTests\Telegram;

use hexa_package_telegram\Domains\Bot\TelegramBotClient;
use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\Webhooks\TelegramWebhookService;
use hexa_package_telegram\Domains\Webhooks\TelegramWebhookSecretService;
use hexa_package_telegram\Http\Controllers\TelegramWebhookController;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class TelegramWebhookSecurityTest extends TestCase
{
    public function test_set_webhook_supplies_telegrams_secret_token(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);
        $config = Mockery::mock(TelegramConfigRepository::class);
        $config->shouldReceive('getBotToken')->once()->with('news')->andReturn('123456:test-token');
        $secrets = Mockery::mock(TelegramWebhookSecretService::class);
        $secrets->shouldReceive('forBot')->once()->with('news')->andReturn('signed-webhook-secret');

        $result = (new TelegramBotClient($config, $secrets))->setWebhook('https://publish.example/telegram/webhook', 'news');

        $this->assertTrue($result->success);
        Http::assertSent(static fn (Request $request): bool =>
            $request['url'] === 'https://publish.example/telegram/webhook'
            && $request['secret_token'] === 'signed-webhook-secret'
        );
    }

    public function test_webhook_route_is_rate_limited(): void
    {
        $route = app('router')->getRoutes()->getByName('telegram.webhook');

        $this->assertNotNull($route);
        $this->assertContains('throttle:webhook', $route->gatherMiddleware());
    }

    public function test_unsigned_webhook_is_rejected_before_update_dispatch(): void
    {
        $webhooks = Mockery::mock(TelegramWebhookService::class);
        $webhooks->shouldNotReceive('handleIncomingUpdate');
        $config = Mockery::mock(TelegramConfigRepository::class);
        $config->shouldReceive('getBots')->once()->andReturn([['key' => 'default']]);
        $secrets = Mockery::mock(TelegramWebhookSecretService::class);
        $secrets->shouldReceive('resolveBotKey')->once()->with(['default'], 'invalid')->andReturn(null);
        $request = HttpRequest::create('/telegram/webhook', 'POST', [], [], [], [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'invalid',
        ], json_encode(['update_id' => 12]));

        $response = (new TelegramWebhookController($webhooks, $config, $secrets))->handle($request);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_signed_webhook_dispatches_with_the_verified_bot_identity(): void
    {
        $payload = ['update_id' => 99];
        $webhooks = Mockery::mock(TelegramWebhookService::class);
        $webhooks->shouldReceive('handleIncomingUpdate')->once()->with($payload, 'news');
        $config = Mockery::mock(TelegramConfigRepository::class);
        $config->shouldReceive('getBots')->once()->andReturn([['key' => 'default'], ['key' => 'news']]);
        $secrets = Mockery::mock(TelegramWebhookSecretService::class);
        $secrets->shouldReceive('resolveBotKey')->once()->with(['default', 'news'], 'valid')->andReturn('news');
        $request = HttpRequest::create('/telegram/webhook', 'POST', $payload, [], [], [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'valid',
        ]);

        $response = (new TelegramWebhookController($webhooks, $config, $secrets))->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
