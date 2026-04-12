<?php

namespace hexa_package_telegram\Http\Controllers;

use hexa_package_telegram\Domains\Webhooks\TelegramWebhookService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramWebhookService $webhooks,
    ) {}

    public function handle(Request $request)
    {
        $this->webhooks->handleIncomingUpdate($request->all());

        return response('ok');
    }
}
