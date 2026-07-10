<?php

namespace hexa_package_telegram\Http\Controllers;

use hexa_package_telegram\Domains\Webhooks\TelegramWebhookService;
use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\Webhooks\TelegramWebhookSecretService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramWebhookService $webhooks,
        protected TelegramConfigRepository $config,
        protected TelegramWebhookSecretService $webhookSecrets,
    ) {}

    public function handle(Request $request)
    {
        $botKeys = array_values(array_filter(array_map(
            static fn (array $bot): string => (string) ($bot['key'] ?? ''),
            $this->config->getBots()
        )));
        $providedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
        $botKey = $this->webhookSecrets->resolveBotKey($botKeys, $providedSecret);
        if ($botKey === null) {
            return response()->json(['success' => false, 'message' => 'Invalid webhook signature.'], 403);
        }

        $this->webhooks->handleIncomingUpdate($request->all(), $botKey);

        return response('ok');
    }
}
