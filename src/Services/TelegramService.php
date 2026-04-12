<?php

namespace hexa_package_telegram\Services;

use hexa_package_telegram\Contracts\TelegramPushContract;
use hexa_package_telegram\Domains\Bot\TelegramBotClient;

class TelegramService
{
    public function __construct(
        protected TelegramBotClient $botClient,
        protected TelegramPushContract $push,
    ) {}

    public function testBotToken(?string $token = null): array
    {
        return $this->botClient->testBotToken($token)->toArray();
    }

    public function setWebhook(string $url): array
    {
        return $this->botClient->setWebhook($url)->toArray();
    }

    public function getWebhookInfo(): array
    {
        return $this->botClient->getWebhookInfo();
    }

    public function sendMessage(string $message, ?string $chatId = null): array
    {
        return $this->push->sendText($message, $chatId)->toArray();
    }

    public function sendMessageWithButtons(string $message, array $buttons, ?string $chatId = null): array
    {
        return $this->push->sendRichMessage($message, $buttons, $chatId)->toArray();
    }

    /**
     * Deprecated compatibility wrapper.
     */
    public function sendArticleApproval(int $articleId, string $title, string $siteName, int $wordCount, ?string $chatId = null): array
    {
        $message = "<b>Article Ready for Review</b>\n\n"
            . "<b>Title:</b> {$title}\n"
            . "<b>Site:</b> {$siteName}\n"
            . "<b>Words:</b> {$wordCount}\n"
            . "<b>ID:</b> {$articleId}";

        $buttons = [
            [
                ['text' => 'Approve & Publish', 'callback_data' => "publish:{$articleId}"],
                ['text' => 'Redraft', 'callback_data' => "redraft:{$articleId}"],
            ],
            [
                ['text' => 'Re-pick Photos', 'callback_data' => "rephotos:{$articleId}"],
                ['text' => 'View Article', 'callback_data' => "view:{$articleId}"],
            ],
        ];

        return $this->sendMessageWithButtons($message, $buttons, $chatId);
    }

    /**
     * Deprecated compatibility wrapper.
     */
    public function notifyPublished(string $title, string $siteName, ?string $wpUrl = null, ?string $chatId = null): array
    {
        $message = "Published: <b>{$title}</b>\n"
            . "Site: {$siteName}";

        if ($wpUrl) {
            $message .= "\n<a href=\"{$wpUrl}\">View Post</a>";
        }

        return $this->sendMessage($message, $chatId);
    }
}
