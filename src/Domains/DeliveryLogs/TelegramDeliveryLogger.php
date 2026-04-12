<?php

namespace hexa_package_telegram\Domains\DeliveryLogs;

use hexa_core\Services\GenericService;
use hexa_package_telegram\DTOs\TelegramDeliveryResult;

class TelegramDeliveryLogger
{
    public function __construct(
        protected GenericService $generic,
    ) {}

    public function logDelivery(string $action, string $target, TelegramDeliveryResult $result, array $context = []): void
    {
        $level = $result->success ? 'info' : 'warning';

        $this->generic->log($level, 'telegram.' . $action . ': ' . $result->message, array_merge([
            'target'  => $target,
            'success' => $result->success,
        ], $context, $result->data ? ['data' => $result->data] : []));
    }

    public function logWebhook(string $message, array $context = []): void
    {
        $this->generic->log('info', 'telegram.webhook: ' . $message, $context);
    }
}
