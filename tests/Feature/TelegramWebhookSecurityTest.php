<?php

namespace HexaPackageSmokeTests\LaravelHexaPackageTelegram;

use hexa_core\Services\CredentialService;
use hexa_package_telegram\Domains\Bot\TelegramBotClient;
use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\Webhooks\TelegramWebhookService;
use hexa_package_telegram\Http\Controllers\TelegramWebhookController;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class TelegramWebhookSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telegram.webhook_max_payload_bytes' => 1048576,
            'telegram.webhook_secret_token' => 'configured-webhook-secret',
        ]);
    }

    public function test_missing_or_invalid_secret_is_rejected_without_dispatch(): void
    {
        $webhooks = Mockery::mock(TelegramWebhookService::class);
        $webhooks->shouldNotReceive('handleIncomingUpdate');
        $config = Mockery::mock(TelegramConfigRepository::class);
        $config->shouldReceive('getWebhookSecretToken')->twice()->andReturn('configured-webhook-secret');
        $controller = new TelegramWebhookController($webhooks, $config);

        $missing = $controller->handle($this->request(['update_id' => 1]));
        $invalid = $controller->handle($this->request(['update_id' => 2], 'wrong-secret'));

        $this->assertSame(403, $missing->getStatusCode());
        $this->assertSame(403, $invalid->getStatusCode());
        $this->assertStringContainsString('no-store', (string) $missing->headers->get('Cache-Control'));
    }

    public function test_valid_secret_dispatches_the_update_and_disables_caching(): void
    {
        $payload = ['update_id' => 123, 'message' => ['text' => 'hello']];
        $webhooks = Mockery::mock(TelegramWebhookService::class);
        $webhooks->shouldReceive('handleIncomingUpdate')->once()->with($payload);
        $config = Mockery::mock(TelegramConfigRepository::class);
        $config->shouldReceive('getWebhookSecretToken')->once()->andReturn('configured-webhook-secret');

        $response = (new TelegramWebhookController($webhooks, $config))->handle(
            $this->request($payload, 'configured-webhook-secret'),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_payload_size_is_bounded_before_dispatch(): void
    {
        config(['telegram.webhook_max_payload_bytes' => 1024]);
        $webhooks = Mockery::mock(TelegramWebhookService::class);
        $webhooks->shouldNotReceive('handleIncomingUpdate');
        $config = Mockery::mock(TelegramConfigRepository::class);
        $config->shouldReceive('getWebhookSecretToken')->once()->andReturn('configured-webhook-secret');

        $response = (new TelegramWebhookController($webhooks, $config))->handle(
            $this->request(['update_id' => 123, 'message' => ['text' => str_repeat('x', 2048)]], 'configured-webhook-secret'),
        );

        $this->assertSame(413, $response->getStatusCode());
    }

    public function test_webhook_registration_sends_the_same_secret_token_to_telegram(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);
        $config = Mockery::mock(TelegramConfigRepository::class);
        $config->shouldReceive('getBotToken')->once()->with('alerts')->andReturn('123456:bot-token');
        $config->shouldReceive('getWebhookSecretToken')->once()->andReturn('configured-webhook-secret');

        (new TelegramBotClient($config))->setWebhook('https://example.test/telegram/webhook', 'alerts');

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === 'https://api.telegram.org/bot123456:bot-token/setWebhook'
                && $request['url'] === 'https://example.test/telegram/webhook'
                && $request['secret_token'] === 'configured-webhook-secret';
        });
    }

    public function test_repository_derives_a_stable_secret_when_no_explicit_token_exists(): void
    {
        config([
            'telegram.webhook_secret_token' => '',
            'app.key' => 'base64:application-key-material',
        ]);
        $credentials = Mockery::mock(CredentialService::class);
        $repository = new TelegramConfigRepository($credentials);

        $secret = $repository->getWebhookSecretToken();

        $this->assertSame(64, strlen((string) $secret));
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', (string) $secret);
        $this->assertSame($secret, $repository->getWebhookSecretToken());
    }

    public function test_public_webhook_route_has_the_named_throttle(): void
    {
        $route = Route::getRoutes()->getByName('telegram.webhook');

        $this->assertNotNull($route);
        $this->assertContains('throttle:telegram-webhook', $route->gatherMiddleware());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function request(array $payload, ?string $secret = null): Request
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($secret !== null) {
            $server['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] = $secret;
        }

        return Request::create(
            '/telegram/webhook',
            'POST',
            [],
            [],
            [],
            $server,
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
