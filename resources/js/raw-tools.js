const telegramRawConfig = JSON.parse(document.getElementById('telegram-raw-config').textContent);
const CSRF_TOKEN = telegramRawConfig.csrf;
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

    fetch(telegramRawConfig.sendUrl, { method: 'POST', body: body })
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

    fetch(telegramRawConfig.webhookInfoUrl, { method: 'POST', body: body })
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

    fetch(telegramRawConfig.setWebhookUrl, { method: 'POST', body: body })
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
