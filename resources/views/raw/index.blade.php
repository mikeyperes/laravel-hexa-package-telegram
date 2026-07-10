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
    @include("telegram::raw.scripts")
@endpush
@endsection
