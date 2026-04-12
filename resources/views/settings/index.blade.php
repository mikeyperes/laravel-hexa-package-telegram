@extends('layouts.app')

@section('title', 'Telegram Settings')

@section('content')
<div x-data="telegramSettingsPage()" class="space-y-6">

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Telegram Bot Configuration</h1>
                <p class="mt-1 text-sm text-gray-500">Canonical Telegram settings for push notifications and Telegram-based 2FA delivery.</p>
            </div>
            <a href="{{ route('telegram.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Open Raw Tools</a>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-gray-500">Bot Status</div>
                <div class="mt-1 text-sm font-medium {{ $status['configured'] ? 'text-green-700' : 'text-red-600' }}">
                    {{ $status['configured'] ? 'Configured' : 'Missing Bot Config' }}
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-gray-500">2FA Method</div>
                <div class="mt-1 text-sm font-medium {{ $telegramMethodOn ? 'text-green-700' : 'text-amber-700' }}">
                    {{ $telegramMethodOn ? 'Enabled In System Policy' : 'Disabled In System Policy' }}
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-gray-500">Webhook URL</div>
                <div class="mt-1 text-xs text-gray-600 break-all">{{ $status['webhook_url'] }}</div>
            </div>
        </div>
    </div>

    <div class="bg-sky-50 border border-sky-200 rounded-xl p-5">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-sky-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="text-sm text-sky-900 space-y-2">
                <p class="font-medium">Setup flow</p>
                <ol class="list-decimal list-inside space-y-1 text-sky-800">
                    <li>Create a bot with <a href="https://t.me/BotFather" target="_blank" class="underline font-medium">BotFather</a>.</li>
                    <li>Store the bot token below using the encrypted credential field.</li>
                    <li>Save the bot username and optional default chat ID.</li>
                    <li>Set the webhook to <code class="bg-sky-100 px-1.5 py-0.5 rounded text-xs">{{ $status['webhook_url'] }}</code>.</li>
                    <li>Enable Telegram as an allowed 2FA method in <a href="{{ route('settings.two-factor') }}" class="underline font-medium">Settings &gt; Two-Factor Auth</a>.</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Bot Credentials</h2>
            <p class="mt-1 text-sm text-gray-500">The token is stored encrypted via core `CredentialService`.</p>
        </div>

        <x-hexa-credential-field
            slug="telegram"
            key-name="bot_token"
            label="Telegram Bot Token"
            :test-url="route('settings.telegram.test')"
            help="Bot token issued by BotFather."
        />

        <form @submit.prevent="save()" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bot Username</label>
                <div class="flex items-center gap-2">
                    <span class="text-gray-400 text-sm">@</span>
                    <input type="text" x-model="form.bot_username" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="your_bot_name">
                </div>
                <p class="mt-1 text-xs text-gray-400">Stored as package-owned Telegram config and reused by core 2FA linking.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Default Chat ID</label>
                <input type="text" x-model="form.default_chat_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono" placeholder="Optional: admin/debug chat id">
                <p class="mt-1 text-xs text-gray-400">Optional fallback for raw tools and manual admin push tests. User-targeted notifications use each user’s linked chat ID.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" :disabled="saving" class="inline-flex items-center px-5 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 disabled:opacity-50">
                    <svg x-show="saving" x-cloak class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="saving ? 'Saving...' : 'Save Settings'"></span>
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Webhook Management</h2>
                <p class="mt-1 text-sm text-gray-500">Telegram webhook delivery for account-link messages and future inbound features.</p>
            </div>
            <button type="button" @click="refreshWebhookInfo()" :disabled="loadingWebhook" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-3 py-2 hover:bg-gray-50 disabled:opacity-50">
                <span x-text="loadingWebhook ? 'Refreshing...' : 'Refresh'"></span>
            </button>
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
            <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Current Webhook</div>
            <div class="text-gray-700 break-all" x-text="webhookInfo.url || 'Not set'"></div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 text-sm">
            <div class="rounded-lg border border-gray-200 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-gray-500">Pending Updates</div>
                <div class="mt-1 text-gray-900" x-text="webhookInfo.pending_update_count ?? 0"></div>
            </div>
            <div class="rounded-lg border border-gray-200 px-4 py-3">
                <div class="text-xs uppercase tracking-wide text-gray-500">Last Error</div>
                <div class="mt-1 text-gray-900 break-words" x-text="webhookInfo.last_error_message || 'None'"></div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
            <div class="text-xs text-gray-500">Default target: <code class="bg-gray-100 px-1.5 py-0.5 rounded">{{ $status['webhook_url'] }}</code></div>
            <button type="button" @click="setWebhook()" :disabled="settingWebhook" class="inline-flex items-center px-4 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-lg hover:bg-sky-700 disabled:opacity-50">
                <svg x-show="settingWebhook" x-cloak class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-text="settingWebhook ? 'Setting...' : 'Set Default Webhook'"></span>
            </button>
        </div>
    </div>

    <template x-if="toast.show">
        <div class="fixed bottom-6 right-6 z-50 max-w-sm w-full rounded-xl border px-5 py-4 shadow-lg"
             :class="toast.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'">
            <div class="text-sm font-medium" x-text="toast.message"></div>
        </div>
    </template>

</div>

@push('scripts')
<script>
function telegramSettingsPage() {
    return {
        form: {
            bot_username: @json($status['bot_username']),
            default_chat_id: @json($status['default_chat_id']),
        },
        saving: false,
        settingWebhook: false,
        loadingWebhook: false,
        webhookInfo: { url: '', pending_update_count: 0, last_error_message: '' },
        toast: { show: false, message: '', type: 'success' },
        toastTimer: null,

        init() {
            this.refreshWebhookInfo();
        },

        showToast(message, type = 'success') {
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toast = { show: true, message, type };
            this.toastTimer = setTimeout(() => { this.toast.show = false; }, 3500);
        },

        async save() {
            this.saving = true;
            try {
                const response = await fetch('{{ route("settings.telegram.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await response.json();
                if (data.success) {
                    this.form.bot_username = data.status.bot_username || this.form.bot_username;
                    this.form.default_chat_id = data.status.default_chat_id || '';
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'Failed to save Telegram settings.', 'error');
                }
            } catch (error) {
                this.showToast('Failed to save Telegram settings.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async refreshWebhookInfo() {
            this.loadingWebhook = true;
            try {
                const response = await fetch('{{ route("settings.telegram.webhook-info") }}', {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();
                if (data.success) {
                    this.webhookInfo = data.info || {};
                } else {
                    this.showToast(data.message || 'Failed to load webhook info.', 'error');
                }
            } catch (error) {
                this.showToast('Failed to load webhook info.', 'error');
            } finally {
                this.loadingWebhook = false;
            }
        },

        async setWebhook() {
            this.settingWebhook = true;
            try {
                const response = await fetch('{{ route("settings.telegram.webhook") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                this.showToast(data.message || (data.success ? 'Webhook set.' : 'Failed to set webhook.'), data.success ? 'success' : 'error');
                if (data.success) {
                    await this.refreshWebhookInfo();
                }
            } catch (error) {
                this.showToast('Failed to set webhook.', 'error');
            } finally {
                this.settingWebhook = false;
            }
        },
    };
}
</script>
@endpush
@endsection
