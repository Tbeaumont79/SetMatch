<?php

namespace App\Service;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ChatService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?HubInterface $mercureHub = null
    ) {}

    /**
     * Créer un nouveau message et publier la mise à jour via Mercure
     */
    public function sendMessage(Chat $chat, User $author, string $content): Message
    {
        $message = new Message();
        $message->setContent($content);
        $message->setAuthor($author);
        $message->setChat($chat);

        // Sauvegarder en base
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Publier via Mercure si disponible
        if ($this->mercureHub) {
            $this->publishMessageUpdate($chat, $message);
        }

        return $message;
    }

    /**
     * Publier une mise à jour de message via Mercure
     */
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

    /**
     * Générer le topic Mercure pour un chat spécifique
     */
    public function getChatTopic(Chat $chat): string
    {
        return sprintf('chat/%d', $chat->getId());
    }

    /**
     * Générer un JWT pour un utilisateur afin qu'il puisse s'abonner aux topics de ses chats
     */
    public function generateUserJWT(User $user): string
    {
        // Récupérer tous les chats de l'utilisateur
        $userChats = $user->getChats();
        $topics = [];

        foreach ($userChats as $chat) {
            $topics[] = $this->getChatTopic($chat);
        }

        // Généer le JWT avec les topics autorisés
        return $this->createJWT($topics);
    }

    /**
     * Créer un JWT pour les topics spécifiés
     */
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

    /**
     * Formater un message pour l'affichage
     */
    public function formatMessageForUser(Message $message, User $currentUser): array
    {
        return [
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'author' => [
                'id' => $message->getAuthor()->getId(),
                'email' => $message->getAuthor()->getEmail(),
                'display_name' => explode('@', $message->getAuthor()->getEmail())[0],
            ],
            'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            'is_mine' => $message->getAuthor() === $currentUser,
        ];
    }

    /**
     * Vérifier si un utilisateur a accès à un chat
     */
    public function canUserAccessChat(Chat $chat, User $user): bool
    {
        return $chat->hasParticipant($user);
    }
}
