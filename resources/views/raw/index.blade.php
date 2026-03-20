@extends('layouts.app')

@section('title', 'Telegram Raw — ' . config('hws.app_name'))
@section('header', 'Telegram — Raw Functions')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Bot Token Status --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <span class="text-sm font-medium text-gray-700">Bot Token:</span>
            @if($tokenConfigured)
                <code class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $maskedToken }}</code>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5"/></svg>
                    Configured
                </span>
            @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5"/></svg>
                    Not Configured
                </span>
            @endif
        </div>
        <div class="flex items-center space-x-3">
            <span class="text-sm font-medium text-gray-700">Default Chat ID:</span>
            @if($chatId)
                <code class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $chatId }}</code>
            @else
                <span class="text-sm text-gray-400">Not set</span>
            @endif
        </div>
    </div>

    {{-- Package Functions Index --}}
    <div class="bg-gray-900 rounded-xl p-6 text-sm font-mono">
        <h2 class="text-white font-semibold mb-3">Telegram Functions</h2>
        <table class="w-full text-left">
            <thead>
                <tr class="text-gray-400 border-b border-gray-700">
                    <th class="py-1.5 px-2">Function</th>
                    <th class="py-1.5 px-2">Method</th>
                    <th class="py-1.5 px-2">Route</th>
                    <th class="py-1.5 px-2">Status</th>
                </tr>
            </thead>
            <tbody class="text-gray-300">
                <tr class="border-b border-gray-800">
                    <td class="py-1.5 px-2">Test bot token (getMe)</td>
                    <td class="py-1.5 px-2 text-blue-400">testBotToken()</td>
                    <td class="py-1.5 px-2 text-green-400">via controller</td>
                    <td class="py-1.5 px-2 text-green-400">LIVE</td>
                </tr>
                <tr class="border-b border-gray-800">
                    <td class="py-1.5 px-2">Send text message</td>
                    <td class="py-1.5 px-2 text-blue-400">sendMessage()</td>
                    <td class="py-1.5 px-2 text-green-400">POST /telegram/send</td>
                    <td class="py-1.5 px-2 text-green-400">LIVE</td>
                </tr>
                <tr class="border-b border-gray-800">
                    <td class="py-1.5 px-2">Send message with buttons</td>
                    <td class="py-1.5 px-2 text-blue-400">sendMessageWithButtons()</td>
                    <td class="py-1.5 px-2 text-yellow-400">service only</td>
                    <td class="py-1.5 px-2 text-yellow-400">NO ROUTE</td>
                </tr>
                <tr class="border-b border-gray-800">
                    <td class="py-1.5 px-2">Get webhook info</td>
                    <td class="py-1.5 px-2 text-blue-400">getWebhookInfo()</td>
                    <td class="py-1.5 px-2 text-green-400">POST /telegram/webhook-info</td>
                    <td class="py-1.5 px-2 text-green-400">LIVE</td>
                </tr>
                <tr class="border-b border-gray-800">
                    <td class="py-1.5 px-2">Set webhook URL</td>
                    <td class="py-1.5 px-2 text-blue-400">setWebhook()</td>
                    <td class="py-1.5 px-2 text-green-400">POST /telegram/set-webhook</td>
                    <td class="py-1.5 px-2 text-green-400">LIVE</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Send Message --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Send Message</h2>

        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message (HTML supported)</label>
                <textarea id="tg-message" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Hello from the raw dev page!"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Chat ID (leave blank for default)</label>
                <input type="text" id="tg-chat-id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="{{ $chatId ?: 'No default set' }}" value="">
            </div>
            <button id="btn-tg-send" onclick="telegramSend()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Send
            </button>
        </div>

        <div id="tg-send-result" class="mt-4"></div>
    </div>

    {{-- Webhook Info --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Webhook Info</h2>

        <button id="btn-tg-webhook-info" onclick="telegramWebhookInfo()" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            Get Webhook Info
        </button>

        <div id="tg-webhook-info-result" class="mt-4"></div>
    </div>

    {{-- Set Webhook --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Set Webhook</h2>

        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Webhook URL</label>
                <input type="url" id="tg-webhook-url" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="https://example.com/webhook/telegram">
            </div>
            <button id="btn-tg-set-webhook" onclick="telegramSetWebhook()" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                Set Webhook
            </button>
        </div>

        <div id="tg-set-webhook-result" class="mt-4"></div>
    </div>
</div>

@push('scripts')
<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const SPINNER_SVG = '<svg class="animate-spin h-4 w-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

function setButtonLoading(btn, loadingText) {
    btn.disabled = true;
    btn._originalHTML = btn.innerHTML;
    btn.innerHTML = SPINNER_SVG + ' ' + loadingText;
}

function resetButton(btn) {
    btn.disabled = false;
    btn.innerHTML = btn._originalHTML;
}

function resultBanner(success, message) {
    var cls = success
        ? 'bg-green-50 border-green-200 text-green-800'
        : 'bg-red-50 border-red-200 text-red-800';
    return '<div class="p-3 rounded-lg text-sm border ' + cls + ' break-words">' + message + '</div>';
}

function telegramSend() {
    var btn = document.getElementById('btn-tg-send');
    var message = document.getElementById('tg-message').value;
    var chatId = document.getElementById('tg-chat-id').value;
    var resultEl = document.getElementById('tg-send-result');

    if (!message.trim()) {
        resultEl.innerHTML = resultBanner(false, 'Message is required.');
        return;
    }

    setButtonLoading(btn, 'Sending...');

    var body = new FormData();
    body.append('_token', CSRF_TOKEN);
    body.append('message', message);
    if (chatId) body.append('chat_id', chatId);

    fetch('{{ route("telegram.send") }}', { method: 'POST', body: body })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var html = resultBanner(data.success, data.message);
            if (data.success && data.data && data.data.message_id) {
                html += '<div class="mt-2 text-xs text-gray-500">Message ID: ' + data.data.message_id + '</div>';
            }
            resultEl.innerHTML = html;
        })
        .catch(function() {
            resultEl.innerHTML = resultBanner(false, 'Request failed.');
        })
        .finally(function() {
            resetButton(btn);
        });
}

function telegramWebhookInfo() {
    var btn = document.getElementById('btn-tg-webhook-info');
    var resultEl = document.getElementById('tg-webhook-info-result');

    setButtonLoading(btn, 'Loading...');

    var body = new FormData();
    body.append('_token', CSRF_TOKEN);

    fetch('{{ route("telegram.webhook-info") }}', { method: 'POST', body: body })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                var d = data.data;
                var html = '<div class="bg-gray-50 rounded-lg border border-gray-200 p-4 text-sm space-y-2">';
                html += '<div><span class="font-medium text-gray-700">URL:</span> <span class="text-gray-900 break-words">' + (d.url || '<em class="text-gray-400">Not set</em>') + '</span></div>';
                html += '<div><span class="font-medium text-gray-700">Pending Updates:</span> <span class="text-gray-900">' + (d.pending_update_count || 0) + '</span></div>';
                if (d.last_error_message) {
                    html += '<div><span class="font-medium text-red-700">Last Error:</span> <span class="text-red-600 break-words">' + d.last_error_message + '</span></div>';
                }
                if (d.last_error_date) {
                    html += '<div><span class="font-medium text-gray-700">Last Error Date:</span> <span class="text-gray-900">' + new Date(d.last_error_date * 1000).toLocaleString() + '</span></div>';
                }
                if (d.max_connections) {
                    html += '<div><span class="font-medium text-gray-700">Max Connections:</span> <span class="text-gray-900">' + d.max_connections + '</span></div>';
                }
                html += '</div>';
                resultEl.innerHTML = html;
            } else {
                resultEl.innerHTML = resultBanner(false, data.message || 'Failed to get webhook info.');
            }
        })
        .catch(function() {
            resultEl.innerHTML = resultBanner(false, 'Request failed.');
        })
        .finally(function() {
            resetButton(btn);
        });
}

function telegramSetWebhook() {
    var btn = document.getElementById('btn-tg-set-webhook');
    var url = document.getElementById('tg-webhook-url').value;
    var resultEl = document.getElementById('tg-set-webhook-result');

    if (!url.trim()) {
        resultEl.innerHTML = resultBanner(false, 'Webhook URL is required.');
        return;
    }

    setButtonLoading(btn, 'Setting...');

    var body = new FormData();
    body.append('_token', CSRF_TOKEN);
    body.append('url', url);

    fetch('{{ route("telegram.set-webhook") }}', { method: 'POST', body: body })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            resultEl.innerHTML = resultBanner(data.success, data.message);
        })
        .catch(function() {
            resultEl.innerHTML = resultBanner(false, 'Request failed.');
        })
        .finally(function() {
            resetButton(btn);
        });
}
</script>
@endpush
@endsection
