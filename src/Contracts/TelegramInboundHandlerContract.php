<?php

namespace hexa_package_telegram\Contracts;

interface TelegramInboundHandlerContract
{
    public function supports(array $payload): bool;

    public function handle(array $payload): bool;
}
