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
        return view("telegram::settings.index", [
            "status" => $this->config->getStatus(),
            "telegramMethodOn" => Setting::getValue("2fa_method_telegram", "1") === "1",
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "bot_key" => "nullable|string|max:64",
            "key" => "nullable|string|max:64",
            "name" => "required|string|max:128",
            "purpose" => "nullable|string|max:255",
            "bot_username" => "required|string|max:128",
            "default_chat_id" => "nullable|string|max:128",
            "bot_token" => "nullable|string|max:512",
            "make_active" => "nullable|boolean",
        ]);

        $currentKey = $validated["bot_key"] ?: null;
        $bot = $this->config->saveBot([
            "key" => $validated["key"] ?? null,
            "name" => $validated["name"],
            "purpose" => $validated["purpose"] ?? "",
            "bot_username" => $validated["bot_username"],
            "default_chat_id" => $validated["default_chat_id"] ?? "",
        ], $currentKey);

        if (!empty($validated["bot_token"])) {
            $this->config->storeBotToken($validated["bot_token"], $bot["key"]);
            $bot = $this->config->findBot($bot["key"]) ?: $bot;
        }

        if ($request->boolean("make_active")) {
            $bot = $this->config->setActiveBot($bot["key"]);
        }

        return response()->json([
            "success" => true,
            "message" => "Telegram bot saved.",
            "bot" => $bot,
            "status" => $this->config->getStatus(),
        ]);
    }

    public function activate(string $botKey): JsonResponse
    {
        try {
            $bot = $this->config->setActiveBot($botKey);
        } catch (\Throwable $e) {
            return response()->json(["success" => false, "message" => $e->getMessage()], 422);
        }

        return response()->json([
            "success" => true,
            "message" => "Active Telegram bot updated.",
            "bot" => $bot,
            "status" => $this->config->getStatus(),
        ]);
    }

    public function test(Request $request, ?string $botKey = null): JsonResponse
    {
        $key = $botKey ?: $request->input("bot_key");
        return response()->json($this->botClient->testBotToken(null, $key)->toArray());
    }

    public function setWebhook(Request $request, ?string $botKey = null): JsonResponse
    {
        $key = $botKey ?: $request->input("bot_key");
        return response()->json($this->webhooks->setDefaultWebhook($key)->toArray());
    }

    public function webhookInfo(Request $request, ?string $botKey = null): JsonResponse
    {
        $key = $botKey ?: $request->query("bot_key");
        return response()->json([
            "success" => true,
            "info" => $this->webhooks->getWebhookInfo($key),
        ]);
    }
}
