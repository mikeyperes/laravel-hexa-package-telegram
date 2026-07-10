const telegramSettingsConfig = JSON.parse(document.getElementById('telegram-settings-config').textContent);
function telegramSettingsPage() {
    return {
        bots: telegramSettingsConfig.bots,
        activeBotKey: telegramSettingsConfig.activeBotKey,
        selectedKey: telegramSettingsConfig.activeBotKey,
        updateUrl: telegramSettingsConfig.updateUrl,
        botBaseUrl: telegramSettingsConfig.botBaseUrl,
        csrf: document.querySelector("meta[name=csrf-token]").content,
        form: {},
        webhookInfo: { url: "", pending_update_count: 0, last_error_message: "" },
        busy: { save: false, test: false, activate: false, webhook: false, webhookInfo: false },
        toast: { show: false, message: "", type: "success" },
        toastTimer: null,

        init() {
            const active = this.bots.find((bot) => bot.key === this.activeBotKey) || this.bots[0] || null;
            if (active) {
                this.selectBot(active);
            } else {
                this.newBot();
            }
        },

        selectedBot() {
            return this.bots.find((bot) => bot.key === this.selectedKey) || null;
        },

        activeBotName() {
            const bot = this.bots.find((item) => item.key === this.activeBotKey);
            return bot ? bot.name : "None selected";
        },

        configuredCount() {
            return this.bots.filter((bot) => bot.configured).length;
        },

        selectBot(bot) {
            this.selectedKey = bot.key;
            this.form = {
                bot_key: bot.key,
                key: bot.key,
                name: bot.name || "",
                purpose: bot.purpose || "",
                bot_username: bot.bot_username || "",
                default_chat_id: bot.default_chat_id || "",
                bot_token: "",
                make_active: bot.key === this.activeBotKey,
            };
            this.webhookInfo = { url: "", pending_update_count: 0, last_error_message: "" };
            this.refreshWebhookInfo();
        },

        newBot() {
            this.selectedKey = null;
            this.form = { bot_key: "", key: "", name: "", purpose: "", bot_username: "", default_chat_id: "", bot_token: "", make_active: false };
            this.webhookInfo = { url: "", pending_update_count: 0, last_error_message: "" };
        },

        updateStatus(status, selectedKey = null) {
            this.bots = status.bots || [];
            this.activeBotKey = status.active_bot_key || this.activeBotKey;
            const key = selectedKey || this.selectedKey || this.activeBotKey;
            const bot = this.bots.find((item) => item.key === key) || this.bots.find((item) => item.key === this.activeBotKey) || this.bots[0];
            if (bot) {
                this.selectBot(bot);
            }
        },

        async saveBot() {
            this.busy.save = true;
            try {
                const response = await fetch(this.updateUrl, {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": this.csrf },
                    body: JSON.stringify(this.form),
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || "Failed to save Telegram bot.");
                }
                this.showToast(data.message || "Telegram bot saved.", "success");
                this.updateStatus(data.status, data.bot ? data.bot.key : null);
            } catch (error) {
                this.showToast(error.message || "Failed to save Telegram bot.", "error");
            } finally {
                this.busy.save = false;
            }
        },

        endpoint(suffix) {
            return `${this.botBaseUrl}/${encodeURIComponent(this.form.bot_key)}${suffix}`;
        },

        async activateBot() {
            if (!this.form.bot_key) return;
            this.busy.activate = true;
            try {
                const response = await fetch(this.endpoint("/activate"), { method: "POST", headers: { "Accept": "application/json", "X-CSRF-TOKEN": this.csrf } });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || "Failed to activate bot.");
                this.showToast(data.message || "Active bot updated.", "success");
                this.updateStatus(data.status, data.bot ? data.bot.key : null);
            } catch (error) {
                this.showToast(error.message || "Failed to activate bot.", "error");
            } finally {
                this.busy.activate = false;
            }
        },

        async testBot() {
            if (!this.form.bot_key) return;
            this.busy.test = true;
            try {
                const response = await fetch(this.endpoint("/test"), { method: "POST", headers: { "Accept": "application/json", "X-CSRF-TOKEN": this.csrf } });
                const data = await response.json();
                this.showToast(data.message || (data.success ? "Bot test passed." : "Bot test failed."), data.success ? "success" : "error");
            } catch (error) {
                this.showToast("Bot test failed.", "error");
            } finally {
                this.busy.test = false;
            }
        },

        async setWebhook() {
            if (!this.form.bot_key) return;
            this.busy.webhook = true;
            try {
                const response = await fetch(this.endpoint("/webhook"), { method: "POST", headers: { "Accept": "application/json", "X-CSRF-TOKEN": this.csrf } });
                const data = await response.json();
                this.showToast(data.message || (data.success ? "Webhook set." : "Failed to set webhook."), data.success ? "success" : "error");
                await this.refreshWebhookInfo();
            } catch (error) {
                this.showToast("Failed to set webhook.", "error");
            } finally {
                this.busy.webhook = false;
            }
        },

        async refreshWebhookInfo() {
            if (!this.form.bot_key) return;
            this.busy.webhookInfo = true;
            try {
                const response = await fetch(this.endpoint("/webhook-info"), { headers: { "Accept": "application/json" } });
                const data = await response.json();
                this.webhookInfo = data.info || { url: "", pending_update_count: 0, last_error_message: "" };
            } catch (error) {
                this.webhookInfo = { url: "", pending_update_count: 0, last_error_message: "Unable to load webhook info." };
            } finally {
                this.busy.webhookInfo = false;
            }
        },

        showToast(message, type = "success") {
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toast = { show: true, message, type };
            this.toastTimer = setTimeout(() => { this.toast.show = false; }, 3500);
        },
    };
}
