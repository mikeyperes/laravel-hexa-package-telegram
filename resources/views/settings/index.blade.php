@extends("layouts.app")

@section("title", "Telegram Settings")

@section("content")
<style>
    /* Telegram settings: robust layout (compiled bundle purges lg:col-span-2 + space-y-*) */
    .tg-bot-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1.25rem 1.25rem; align-items:start; }
    @media (max-width:768px){ .tg-bot-grid { grid-template-columns:1fr; } }
    .tg-col-full { grid-column:1 / -1; }
    .tg-guide-steps > li { display:flex; gap:1rem; }
    .tg-guide-steps > li + li { margin-top:1.5rem; }
    .tg-guide-bullets { padding-left:1.25rem; margin-top:0.375rem; }
    .tg-guide-bullets > li + li { margin-top:0.5rem; }
    .tg-split { display:flex; flex-direction:column; gap:1.5rem; }
    /* revive purged backgrounds so cards are not see-through (canonical Tailwind values) */
    .bg-white { background-color:#ffffff; }
    .bg-gray-50 { background-color:#f9fafb; }
    .bg-gray-100 { background-color:#f3f4f6; }
    .bg-green-50 { background-color:#f0fdf4; }
    .bg-green-100 { background-color:#dcfce7; }
    .bg-blue-50 { background-color:#eff6ff; }
    .bg-red-50 { background-color:#fef2f2; }
    .bg-red-100 { background-color:#fee2e2; }
    .bg-amber-50 { background-color:#fffbeb; }
    .bg-sky-50 { background-color:#f0f9ff; }
    .bg-sky-50\/40 { background-color:#f0f9ff; }
    .tg-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem 1.5rem; }
    @media (min-width:768px){ .tg-stats { grid-template-columns:repeat(4,minmax(0,1fr)); } }
    /* one control radius; cards keep rounded-xl */
    #tg-page button, #tg-page input, #tg-page textarea, #tg-page select,
    #tg-page a[class*="inline-flex"], #tg-page .border-gray-300 { border-radius:0.5rem !important; }
    /* consistent spacing (bundle purges space-y-*) so sections do not touch */
    #tg-page > * + * { margin-top:1.5rem; }
    #tg-page .space-y-5 > * + * { margin-top:1.25rem; }
    #tg-page .space-y-4 > * + * { margin-top:1rem; }
    #tg-page .space-y-3 > * + * { margin-top:0.75rem; }
    #tg-page .space-y-2 > * + * { margin-top:0.5rem; }
    /* keep buttons from stretching tall when a flex row stretches (lg:items-start purged) */
    #tg-page button, #tg-page a[class*="inline-flex"] { align-self:center; }
</style>
<div id="tg-page" x-data="telegramSettingsPage()" x-init="init()" class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Telegram Bots</h1>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Register one or more Telegram bots by purpose and choose which one packages use by default. Tokens are encrypted by the core credential service.</p>
            </div>
            <a href="{{ route("telegram.index") }}" class="shrink-0 inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Raw tools</a>
        </div>
        <div class="tg-stats mt-5 border-t border-gray-100 pt-4">
            <div class="min-w-0">
                <div class="text-xs text-gray-500">Active bot</div>
                <div class="mt-0.5 truncate text-sm font-semibold text-gray-900" x-text="activeBotName()"></div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Configured</div>
                <div class="mt-0.5 text-sm font-semibold text-gray-900" x-text="configuredCount() + ` of ` + bots.length"></div>
            </div>
            <div>
                <div class="text-xs text-gray-500">2FA policy</div>
                <div class="mt-0.5 text-sm font-semibold {{ $telegramMethodOn ? "text-green-700" : "text-amber-700" }}">{{ $telegramMethodOn ? "Enabled" : "Disabled" }}</div>
            </div>
            <div class="min-w-0">
                <div class="text-xs text-gray-500">Webhook endpoint</div>
                <div class="mt-0.5 break-all text-xs font-medium text-gray-600">{{ $status["webhook_url"] }}</div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-6 shadow-sm">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-600">Setup guide</p>
                <h2 class="mt-1 text-xl font-semibold text-gray-950">How to connect a Telegram bot &mdash; step by step</h2>
                <p class="mt-1 text-sm text-gray-600">Do these in order. Every link opens in a new tab. You only need to do this once per bot.</p>
            </div>
            <a href="https://core.telegram.org/bots#how-do-i-create-a-bot" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-xl border border-sky-300 bg-white px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-50">Official Telegram docs
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5h5m0 0v5m0-5L10 14M19 19H5V5h7"/></svg></a>
        </div>

        <ol class="mt-6 tg-guide-steps">
            <li class="flex gap-4">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-600 text-sm font-bold text-white">1</span>
                <div class="min-w-0">
                    <div class="text-sm font-bold text-gray-900">Create the bot inside BotFather</div>
                    <ul class="mt-1.5 list-disc tg-guide-bullets text-sm text-gray-700">
                        <li>Open <a href="https://t.me/BotFather" target="_blank" rel="noopener" class="font-semibold text-sky-700 underline">@@BotFather</a> in Telegram and press <strong>Start</strong>.</li>
                        <li>Send <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[12px]">/newbot</code>.</li>
                        <li>Type a <strong>display name</strong> (e.g. <em>JPN Approval Bot</em>).</li>
                        <li>Type a <strong>username</strong> that must end in <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[12px]">bot</code> (e.g. <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[12px]">jpn_approval_bot</code>).</li>
                        <li>BotFather replies with an <strong>HTTP API token</strong> like <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[12px]">123456789:AAE-xxxxxxxxxxxxxxxxxxxxxxxxxxx</code>. Copy it &mdash; you will paste it in step&nbsp;2.</li>
                    </ul>
                </div>
            </li>

            <li class="flex gap-4">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-600 text-sm font-bold text-white">2</span>
                <div class="min-w-0">
                    <div class="text-sm font-bold text-gray-900">Register the bot on this page</div>
                    <ul class="mt-1.5 list-disc tg-guide-bullets text-sm text-gray-700">
                        <li>In <strong>Registered Bots</strong> (left) click <strong>Add bot</strong>, or select an existing bot to edit it.</li>
                        <li>Fill in <strong>Bot name</strong> and <strong>Bot username</strong> (without the @@).</li>
                        <li>Paste the token from step&nbsp;1 into the yellow <strong>Bot token</strong> box.</li>
                        <li>Click <strong>Save bot</strong>. The token is encrypted by the core credential service &mdash; the field then shows a masked value instead of the raw token.</li>
                    </ul>
                </div>
            </li>

            <li class="flex gap-4">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-600 text-sm font-bold text-white">3</span>
                <div class="min-w-0">
                    <div class="text-sm font-bold text-gray-900">Find the Chat ID (where messages get delivered)</div>
                    <ul class="mt-1.5 list-disc tg-guide-bullets text-sm text-gray-700">
                        <li><strong>For a group:</strong> add your new bot to the Telegram group, then send any message in that group.</li>
                        <li><strong>For a direct chat:</strong> open your bot by its <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[12px]">@@username</code> and press <strong>Start</strong>.</li>
                        <li>Then open this URL in a new tab, replacing <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[12px]">&lt;token&gt;</code> with your bot token:<br>
                            <code class="mt-1 inline-block break-all rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[12px]">https://api.telegram.org/bot&lt;token&gt;/getUpdates</code></li>
                        <li>In the JSON find <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[12px]">"chat":{"id": &hellip;}</code>. <strong>Group</strong> IDs are negative (e.g. <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[12px]">-1001234567890</code>); <strong>personal</strong> IDs are positive.</li>
                        <li>Shortcut: message <a href="https://t.me/RawDataBot" target="_blank" rel="noopener" class="font-semibold text-sky-700 underline">@@RawDataBot</a> or <a href="https://t.me/userinfobot" target="_blank" rel="noopener" class="font-semibold text-sky-700 underline">@@userinfobot</a> to read an ID instantly.</li>
                        <li>Paste the ID into <strong>Default chat ID</strong> and click <strong>Save bot</strong> again.</li>
                    </ul>
                </div>
            </li>

            <li class="flex gap-4">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-600 text-sm font-bold text-white">4</span>
                <div class="min-w-0">
                    <div class="text-sm font-bold text-gray-900">Connect the webhook (required for Approve / Deny buttons)</div>
                    <ul class="mt-1.5 list-disc tg-guide-bullets text-sm text-gray-700">
                        <li>With the bot selected, scroll to <strong>Selected Bot Webhook</strong> and click <strong>Set webhook</strong>. This points the bot at this site so button taps (Approve / Deny) come back here.</li>
                        <li>Click <strong>Refresh webhook</strong> and confirm <strong>Current Telegram URL</strong> is set and <strong>Pending updates</strong> is 0 with no <strong>Last error</strong>.</li>
                        <li>Optional: to let the bot read every group message (not just commands), in BotFather send <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[12px]">/setprivacy</code> &rarr; pick the bot &rarr; <strong>Disable</strong>.</li>
                    </ul>
                </div>
            </li>

            <li class="flex gap-4">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-600 text-sm font-bold text-white">5</span>
                <div class="min-w-0">
                    <div class="text-sm font-bold text-gray-900">Test, then make it active</div>
                    <ul class="mt-1.5 list-disc tg-guide-bullets text-sm text-gray-700">
                        <li>Click <strong>Test bot</strong> &mdash; a test message should arrive in the chat. If it fails, re-check the token (step&nbsp;2) and the chat ID (step&nbsp;3).</li>
                        <li>Tick <strong>Make this the active default bot</strong> (or press <strong>Make active</strong>) so packages without a specific bot key use this bot.</li>
                        <li>Per-feature bots (e.g. the JPN approval bot) are selected by their <strong>Bot key</strong>; you do not need to make those active.</li>
                    </ul>
                </div>
            </li>
        </ol>
    </div>

    <div class="tg-split">
        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-gray-950">Registered Bots</h2>
                    <button type="button" @click="newBot()" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add bot</button>
                </div>

                <div class="mt-4 space-y-3">
                    <template x-for="bot in bots" :key="bot.key">
                        <button type="button" @click="selectBot(bot)" class="w-full rounded-xl border p-4 text-left transition hover:border-sky-300" :class="bot.key === selectedKey ? `border-sky-400 bg-sky-50` : `border-gray-200 bg-white`">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-gray-950" x-text="bot.name"></div>
                                    <div class="mt-1 text-xs text-gray-500" x-text="bot.key"></div>
                                </div>
                                <span x-show="bot.is_active" class="shrink-0 rounded-full bg-green-100 px-2 py-1 text-[11px] font-semibold text-green-700">Active</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
                                <span class="rounded-full px-2 py-1" :class="bot.configured ? `bg-green-100 text-green-700` : `bg-red-100 text-red-700`" x-text="bot.configured ? `Configured` : `Needs token`"></span>
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-600" x-text="bot.bot_username ? `@` + bot.bot_username : `No username`"></span>
                            </div>
                            <p class="mt-3 line-clamp-2 text-xs text-gray-500" x-text="bot.purpose || `No purpose set yet.`"></p>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500" x-text="form.bot_key ? `Edit bot` : `Register bot`"></p>
                        <h2 class="mt-1 text-xl font-semibold text-gray-950" x-text="form.name || `New Telegram Bot`"></h2>
                        <p class="mt-1 text-sm text-gray-500">Use a separate bot for JPN approvals, billing alerts, or any package that needs isolated Telegram identity.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="activateBot()" x-show="form.bot_key && form.bot_key !== activeBotKey" :disabled="busy.activate" class="inline-flex items-center justify-center rounded-lg border border-green-300 bg-white px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50 disabled:opacity-50" x-text="busy.activate ? `Activating...` : `Make active`"></button>
                        <button type="button" @click="testBot()" x-show="form.bot_key" :disabled="busy.test" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50" x-text="busy.test ? `Testing...` : `Test bot`"></button>
                    </div>
                </div>

                <form @submit.prevent="saveBot()" class="mt-6 tg-bot-grid">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Bot key</label>
                        <input type="text" x-model="form.key" :readonly="!!form.bot_key" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm font-mono focus:border-sky-500 focus:ring-sky-500 disabled:opacity-60" placeholder="jpn_approval">
                        <p class="mt-1 text-xs text-gray-400">Stable internal key. Existing keys are locked so credentials remain attached.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Bot name</label>
                        <input type="text" x-model="form.name" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:ring-sky-500" placeholder="JPN Approval Bot">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Bot username</label>
                        <div class="mt-1 flex rounded-xl border border-gray-300 focus-within:border-sky-500 focus-within:ring-1 focus-within:ring-sky-500">
                            <span class="flex items-center px-3 text-sm text-gray-400">@</span>
                            <input type="text" x-model="form.bot_username" class="w-full rounded-r-xl border-0 px-0 py-2 text-sm focus:ring-0" placeholder="your_bot_name">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Default chat ID</label>
                        <input type="text" x-model="form.default_chat_id" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm font-mono focus:border-sky-500 focus:ring-sky-500" placeholder="Group or admin chat ID">
                    </div>
                    <div class="tg-col-full">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Purpose / package assignment note</label>
                        <textarea x-model="form.purpose" rows="2" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-sky-500 focus:ring-sky-500" placeholder="Example: JPN message approval texts only."></textarea>
                    </div>
                    <div class="tg-col-full rounded-xl border border-gray-200 bg-white p-4" x-data="{ editingToken: false }" x-effect="form.bot_key; editingToken = false">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-semibold text-gray-700">Bot token <span class="text-xs font-normal text-gray-400">(API key)</span></label>
                            <span class="flex items-center gap-1.5 text-xs">
                                <span class="w-2 h-2 rounded-full" :class="(selectedBot() && selectedBot().configured) ? 'bg-green-500' : 'bg-red-400'"></span>
                                <span :class="(selectedBot() && selectedBot().configured) ? 'text-green-600' : 'text-red-500'" x-text="(selectedBot() && selectedBot().configured) ? 'Configured' : 'Not set'"></span>
                            </span>
                        </div>
                        <div x-show="(selectedBot() && selectedBot().configured) && !editingToken" class="flex flex-wrap items-center gap-2">
                            <div class="flex-1 min-w-0 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono text-gray-600 break-words" x-text="(selectedBot() && selectedBot().masked_bot_token) ? selectedBot().masked_bot_token : '(hidden)'"></div>
                            <button type="button" @click="editingToken = true; form.bot_token = ''" class="text-xs font-medium text-blue-600 hover:text-blue-800 border border-blue-300 px-3 py-2 rounded-lg hover:bg-blue-50">Change</button>
                            <button type="button" @click="testBot()" :disabled="busy.test" class="inline-flex items-center gap-1.5 text-xs font-medium border border-gray-300 text-gray-600 px-3 py-2 rounded-lg hover:bg-gray-50 disabled:opacity-50"><span x-text="busy.test ? 'Testing...' : 'Test'"></span></button>
                        </div>
                        <div x-show="!(selectedBot() && selectedBot().configured) || editingToken" class="flex flex-wrap items-center gap-2">
                            <input type="password" x-model="form.bot_token" placeholder="Paste the BotFather token, e.g. 123456789:AAE..." class="flex-1 min-w-0 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button type="button" @click="saveBot().then(() => { editingToken = false; })" :disabled="busy.save || !form.bot_token" class="inline-flex items-center gap-1.5 text-xs font-medium bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"><span x-text="busy.save ? 'Saving...' : 'Save'"></span></button>
                            <button type="button" x-show="selectedBot() && selectedBot().configured" @click="editingToken = false; form.bot_token = ''" class="text-xs text-gray-400 hover:text-gray-600 px-2 py-2">Cancel</button>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Stored encrypted by the Telegram credential service. Get it from <a href="https://t.me/BotFather" target="_blank" rel="noopener" class="text-blue-600 hover:underline">@@BotFather</a> &rarr; <code class="rounded bg-gray-100 px-1 py-0.5 font-mono text-[11px]">/newbot</code> (see the setup guide above).</p>
                    </div>
                    <label class="tg-col-full flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="checkbox" x-model="form.make_active" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                        Make this the active default bot for package calls without a specific bot key
                    </label>
                    <div class="tg-col-full flex justify-end">
                        <button type="submit" :disabled="busy.save" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50" x-text="busy.save ? `Saving...` : `Save bot`"></button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Webhook</h2>
                        <p class="mt-1 text-sm text-gray-500">Points the selected bot at this site so Approve / Deny taps come back here.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="refreshWebhookInfo()" x-show="form.bot_key" :disabled="busy.webhookInfo" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50" x-text="busy.webhookInfo ? `Refreshing...` : `Refresh`"></button>
                        <button type="button" @click="setWebhook()" x-show="form.bot_key" :disabled="busy.webhook" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50" x-text="busy.webhook ? `Setting...` : `Set webhook`"></button>
                    </div>
                </div>
                <dl class="mt-4 border-t border-gray-100 divide-y divide-gray-100">
                    <div class="flex items-start justify-between gap-6 py-3">
                        <dt class="shrink-0 text-sm text-gray-500">Current URL</dt>
                        <dd class="min-w-0 break-all text-right text-sm font-medium text-gray-800" x-text="webhookInfo.url || `Not set or token missing`"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-6 py-3">
                        <dt class="shrink-0 text-sm text-gray-500">Pending updates</dt>
                        <dd class="text-sm font-semibold text-gray-900" x-text="webhookInfo.pending_update_count ?? 0"></dd>
                    </div>
                    <div class="flex items-start justify-between gap-6 py-3">
                        <dt class="shrink-0 text-sm text-gray-500">Last error</dt>
                        <dd class="min-w-0 break-words text-right text-sm text-gray-800" x-text="webhookInfo.last_error_message || `None`"></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <template x-if="toast.show">
        <div class="fixed bottom-6 right-6 z-50 max-w-sm rounded-xl border px-5 py-4 shadow-lg" :class="toast.type === `success` ? `border-green-200 bg-green-50 text-green-800` : `border-red-200 bg-red-50 text-red-800`">
            <div class="text-sm font-semibold" x-text="toast.message"></div>
        </div>
    </template>
</div>

@push("scripts")
@include("telegram::settings.scripts")
@endpush
@endsection
