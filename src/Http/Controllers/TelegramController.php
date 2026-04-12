<?php

namespace hexa_package_telegram\Http\Controllers;

use hexa_core\Http\Controllers\Controller;
use hexa_package_telegram\Contracts\TelegramPushContract;
use hexa_package_telegram\Domains\Config\TelegramConfigRepository;
use hexa_package_telegram\Domains\Webhooks\TelegramWebhookService;
use Illuminate\Http\Request;

/**
 * TelegramController — raw/debug tooling for Telegram transport.
 */
class TelegramController extends Controller
{
    public function __construct(
        protected TelegramConfigRepository $config,
        protected TelegramPushContract $push,
        protected TelegramWebhookService $webhooks,
    ) {}

    /**
     * Show the raw development/test page.
     *
     * @return \Illuminate\View\View
     */
    public function raw()
    {
        $status = $this->config->getStatus();

        return view('telegram::raw.index', [
            'tokenConfigured' => $status['configured'],
            'maskedToken'     => $status['masked_bot_token'],
            'chatId'          => $status['default_chat_id'],
        ]);
    }

    /**
     * Send a text message via Telegram.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $result = $this->push->sendText(
            $request->input('message'),
            $request->input('chat_id') ?: null
        )->toArray();

        return response()->json($result);
    }

    /**
     * Get current webhook info from Telegram API.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhookInfo()
    {
        $info = $this->webhooks->getWebhookInfo();

        return response()->json([
            'success' => true,
            'message' => 'Webhook info retrieved.',
            'data' => $info,
        ]);
    }

    /**
     * Set the Telegram bot webhook URL.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setWebhook(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $result = $this->webhooks->setWebhook($request->input('url'))->toArray();

        return response()->json($result);
    }
}
