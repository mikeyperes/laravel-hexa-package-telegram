<?php

namespace hexa_package_telegram\Domains\Recipients;

use hexa_core\Models\User;

class TelegramRecipientResolver
{
    public function resolveChatIdForUser(User $user): ?string
    {
        $chatId = trim((string) $user->telegram_chat_id);
        return $chatId !== '' ? $chatId : null;
    }

    /**
     * @param iterable<User> $users
     * @return array<int, array{user: User, chat_id: string}>
     */
    public function resolveUsersWithChatIds(iterable $users): array
    {
        $resolved = [];

        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $chatId = $this->resolveChatIdForUser($user);
            if ($chatId === null) {
                continue;
            }

            $resolved[] = [
                'user'    => $user,
                'chat_id' => $chatId,
            ];
        }

        return $resolved;
    }
}
