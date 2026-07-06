<?php

namespace hexa_package_telegram\Domains\Webhooks;

use hexa_package_telegram\Contracts\TelegramInboundHandlerContract;

class TelegramInboundRouter
{
    public function dispatch(array $payload): bool
    {
        foreach ($this->handlers() as $handler) {
            try {
                if (!$handler->supports($payload)) {
                    continue;
                }

                if ($handler->handle($payload)) {
                    return true;
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return false;
    }

    protected function handlers(): array
    {
        $handlers = [];

        foreach ((array) config('telegram.inbound_handlers', []) as $handlerClass) {
            if (!is_string($handlerClass) || $handlerClass === "" || !class_exists($handlerClass)) {
                continue;
            }

            $handler = app($handlerClass);
            if ($handler instanceof TelegramInboundHandlerContract) {
                $handlers[] = $handler;
            }
        }

        return $handlers;
    }
}
