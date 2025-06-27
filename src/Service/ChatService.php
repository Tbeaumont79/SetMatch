<?php

namespace App\Service;

use App\Contract\PersistenceInterface;
use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;


class ChatService
{
    public function __construct(
        private readonly PersistenceInterface $persistenceService,
        private ?HubInterface $mercureHub = null
    ) {}


    public function sendMessage(Chat $chat, User $author, string $content): Message
    {
        $message = new Message();
        $message->setContent($content);
        $message->setAuthor($author);
        $message->setChat($chat);

        // Utilisation du service de persistance (DIP)
        $this->persistenceService->persistAndFlush($message);

        // Publier via Mercure si disponible
        if ($this->mercureHub) {
            $this->publishMessageUpdate($chat, $message);
        }

        return $message;
    }


    private function publishMessageUpdate(Chat $chat, Message $message): void
    {
        // Créer le topic spécifique au chat
        $topic = $this->getChatTopic($chat);

        // Préparer les données du message
        $messageData = [
            'type' => 'new_message',
            'chat_id' => $chat->getId(),
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'author' => [
                    'id' => $message->getAuthor()->getId(),
                    'email' => $message->getAuthor()->getEmail(),
                    'display_name' => explode('@', $message->getAuthor()->getEmail())[0],
                ],
                'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ];

        // Créer la mise à jour Mercure
        $update = new Update(
            $topic,
            json_encode($messageData),
            private: true // Seuls les utilisateurs autorisés peuvent voir
        );

        // Publier via Mercure si disponible
        if ($this->mercureHub) {
            $this->mercureHub->publish($update);
        }
    }


    public function getChatTopic(Chat $chat): string
    {
        return sprintf('chat/%d', $chat->getId());
    }


    public function generateUserJWT(User $user): string
    {
        // Récupérer tous les chats de l'utilisateur
        $userChats = $user->getChats();
        $topics = [];

        foreach ($userChats as $chat) {
            $topics[] = $this->getChatTopic($chat);
        }

        // Générer le JWT avec les topics autorisés
        return $this->createJWT($topics);
    }


    private function createJWT(array $topics): string
    {
        $secret = $_ENV['MERCURE_JWT_SECRET'] ?? '!ChangeThisMercureHubJWTSecretKey!';

        $payload = [
            'mercure' => [
                'subscribe' => $topics,
            ],
        ];

        return \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
    }
}
