<?php

namespace hexa_package_telegram\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use hexa_package_telegram\Services\TelegramService;
use hexa_core\Models\Setting;

/**
 * TelegramController — handles raw dev view and API test endpoints.
 */
class TelegramController extends Controller
{
    /**
     * Show the raw development/test page.
     *
     * @return \Illuminate\View\View
     */
    public function raw()
    {
        $token = Setting::getValue('telegram_bot_token', '');
        $chatId = Setting::getValue('telegram_chat_id', '');

        return view('telegram::raw.index', [
            'tokenConfigured' => !empty($token),
            'maskedToken' => $token ? str_repeat('*', 8) . substr($token, -4) : '',
            'chatId' => $chatId,
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

        $service = app(TelegramService::class);
        $result = $service->sendMessage(
            $request->input('message'),
            $request->input('chat_id') ?: null
        );

        return response()->json($result);
    }

    /**
     * Get current webhook info from Telegram API.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhookInfo()
    {
        $service = app(TelegramService::class);
        $info = $service->getWebhookInfo();

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

        $service = app(TelegramService::class);
        $result = $service->setWebhook($request->input('url'));

        return response()->json($result);
    }
}
