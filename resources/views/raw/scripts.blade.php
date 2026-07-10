@php
    $telegramRawFrontendConfig = [
        'csrf' => csrf_token(),
        'sendUrl' => route('telegram.send'),
        'webhookInfoUrl' => route('telegram.webhook-info'),
        'setWebhookUrl' => route('telegram.set-webhook'),
    ];
@endphp
<script type="application/json" id="telegram-raw-config">{!! Illuminate\Support\Js::encode($telegramRawFrontendConfig) !!}</script>
<x-hexa-package-script package="telegram" :version="config('telegram.version')" asset="raw-tools.js" />
