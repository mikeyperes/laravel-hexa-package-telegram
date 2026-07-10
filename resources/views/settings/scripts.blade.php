@php
    $telegramSettingsFrontendConfig = [
        'bots' => $status['bots'],
        'activeBotKey' => $status['active_bot_key'],
        'updateUrl' => route('settings.telegram.update'),
        'botBaseUrl' => url('/settings/telegram/bots'),
    ];
@endphp
<script type="application/json" id="telegram-settings-config">{!! Illuminate\Support\Js::encode($telegramSettingsFrontendConfig) !!}</script>
<x-hexa-package-script package="telegram" :version="config('telegram.version')" asset="settings.js" />
