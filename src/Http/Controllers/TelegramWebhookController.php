<?php

namespace hexa_package_telegram\Http\Controllers;

use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\Webhooks\TelegramWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramWebhookService $webhooks,
        protected TelegramConfigRepository $config,
    ) {}

    public function handle(Request $request): Response
    {
        $expectedSecret = $this->config->getWebhookSecretToken();
        $providedSecret = trim((string) $request->header("X-Telegram-Bot-Api-Secret-Token", ""));
        if (!$expectedSecret || !hash_equals(hash("sha256", $expectedSecret), hash("sha256", $providedSecret))) {
            return $this->response("forbidden", 403);
        }

        $rawBody = (string) $request->getContent();
        $maxPayloadBytes = max(1024, min((int) config("telegram.webhook_max_payload_bytes", 1048576), 2097152));
        if (strlen($rawBody) > $maxPayloadBytes) {
            return $this->response("payload too large", 413);
        }

        $payload = $request->all();
        if ($payload === []) {
            return $this->response("invalid payload", 422);
        }

        $this->webhooks->handleIncomingUpdate($payload);

        return $this->response("ok");
    }

    private function response(string $body, int $status = 200): Response
    {
        return response($body, $status)
            ->header("Cache-Control", "no-store, private, max-age=0")
            ->header("Pragma", "no-cache");
    }
}
