<?php

namespace App\Service;

use App\Contract\ChatManagementInterface;
use App\Contract\PersistenceInterface;
use App\Entity\Chat;
use App\Entity\User;
use App\Repository\ChatRepository;


class ChatManagementService implements ChatManagementInterface
{
    public function __construct(
        private readonly ChatRepository $chatRepository,
        private readonly PersistenceInterface $persistenceService
    ) {}


    public function createOrGetExistingChat(User $initiator, User $participant): Chat
    {
        if ($initiator === $participant) {
            throw new \InvalidArgumentException('Un utilisateur ne peut pas démarrer une conversation avec lui-même');
        }

        // Vérifier si une conversation existe déjà
        $existingChat = $this->chatRepository->findExistingChat($initiator, $participant);
        if ($existingChat) {
            return $existingChat;
        }

        // Créer nouvelle conversation
        $chat = new Chat();
        $chat->addParticipant($initiator);
        $chat->addParticipant($participant);

        $this->persistenceService->persistAndFlush($chat);

        return $chat;
    }


    public function canUserAccessChat(Chat $chat, User $user): bool
    {
        return $chat->hasParticipant($user);
    }
}
