<?php

namespace hexa_package_telegram\Http\Controllers;

use hexa_core\Http\Controllers\Controller;
use hexa_core\Models\Setting;
use hexa_package_telegram\Domains\Bot\TelegramBotClient;
use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\Webhooks\TelegramWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramSettingController extends Controller
{
    public function __construct(
        protected TelegramConfigRepository $config,
        protected TelegramBotClient $botClient,
        protected TelegramWebhookService $webhooks,
    ) {}

    public function index()
    {
        return view('telegram::settings.index', [
            'status'             => $this->config->getStatus(),
            'telegramMethodOn'   => Setting::getValue('2fa_method_telegram', '1') === '1',
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bot_username'    => 'required|string|max:128',
            'default_chat_id' => 'nullable|string|max:128',
        ]);

        $this->config->saveSettings(
            $validated['bot_username'],
            $validated['default_chat_id'] ?? ''
        );

        return response()->json([
            'success' => true,
            'message' => 'Telegram settings saved.',
            'status'  => $this->config->getStatus(),
        ]);
    }

    public function test(): JsonResponse
    {
        return response()->json($this->botClient->testBotToken()->toArray());
    }

    public function setWebhook(): JsonResponse
    {
        return response()->json($this->webhooks->setDefaultWebhook()->toArray());
    }

    public function webhookInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'info'    => $this->webhooks->getWebhookInfo(),
        ]);
    }
}
