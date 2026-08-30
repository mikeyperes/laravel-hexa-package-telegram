<?php
return [
    'version' => '4.0.15',
    'inbound_handlers' => [],
    'webhook_secret_token' => env('HWS_TELEGRAM_WEBHOOK_SECRET_TOKEN', ''),
    'webhook_max_payload_bytes' => (int) env('HWS_TELEGRAM_WEBHOOK_MAX_PAYLOAD_BYTES', 1048576),
    'webhook_throttle_per_minute' => (int) env('HWS_TELEGRAM_WEBHOOK_THROTTLE_PER_MINUTE', 120),
];
