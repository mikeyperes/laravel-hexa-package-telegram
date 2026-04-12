<?php

namespace hexa_package_telegram\DTOs;

class TelegramDeliveryResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?array $data = null,
    ) {}

    public static function success(string $message, ?array $data = null): self
    {
        return new self(true, $message, $data);
    }

    public static function failure(string $message, ?array $data = null): self
    {
        return new self(false, $message, $data);
    }

    /**
     * @return array{success: bool, message: string, data: array|null}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data'    => $this->data,
        ];
    }
}
