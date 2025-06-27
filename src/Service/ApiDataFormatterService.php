<?php

namespace App\Service;

use App\Contract\DataFormatterInterface;
use App\Entity\User;
use App\Entity\Message;


class ApiDataFormatterService implements DataFormatterInterface
{

    public function formatUserForApi(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'display_name' => $this->extractUsernameFromEmail($user->getEmail()),
            'avatar' => $user->getAvatar(),
        ];
    }


    public function formatMessageForApi(Message $message, User $currentUser): array
    {
        return [
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'author' => $this->formatUserForApi($message->getAuthor()),
            'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            'is_mine' => $message->getAuthor() === $currentUser,
        ];
    }


    public function extractUsernameFromEmail(string $email): string
    {
        return explode('@', $email)[0];
    }
}
