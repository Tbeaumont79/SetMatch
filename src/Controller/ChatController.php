<?php

namespace App\Controller;

use App\Contract\ChatManagementInterface;
use App\Contract\DataFormatterInterface;
use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Contrôleur pour la gestion des chats
 * Respecte tous les principes SOLID
 */
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly ChatManagementInterface $chatManagementService,
        private readonly DataFormatterInterface $dataFormatter
    ) {}

    /**
     * Liste les chats de l'utilisateur
     * Respecte SRP - une seule responsabilité : lister les chats
     */
    #[Route('/chat', name: 'app_chat_list', methods: ['GET'])]
    public function list(ChatRepository $chatRepository): Response
    {
        $user = $this->getUser();
        $chats = $chatRepository->findByParticipant($user);

        return $this->render('chat/list.html.twig', [
            'chats' => $chats,
        ]);
    }

    /**
     * Démarre une nouvelle conversation
     * Respecte SRP - une seule responsabilité : créer un chat
     */
    #[Route('/chat/start', name: 'app_chat_start', methods: ['POST'])]
    public function startChat(Request $request, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $participantId = $data['participant_id'] ?? null;

        if (!$participantId) {
            return $this->json(['error' => 'ID du participant requis'], 400);
        }

        $participant = $userRepository->find($participantId);
        if (!$participant) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }

        try {
            // Utilisation du service de gestion de chat (DIP)
            $chat = $this->chatManagementService->createOrGetExistingChat(
                $this->getUser(),
                $participant
            );

            return $this->json(['chat_id' => $chat->getId()]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Affiche un chat spécifique
     * Respecte SRP - une seule responsabilité : afficher un chat
     */
    #[Route('/chat/{id}', name: 'app_chat_show', methods: ['GET'])]
    public function show(Chat $chat): Response
    {
        $user = $this->getUser();

        if (!$this->chatManagementService->canUserAccessChat($chat, $user)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette conversation.');
        }

        return $this->render('chat/show.html.twig', [
            'chat' => $chat,
        ]);
    }

    /**
     * Récupère les messages d'un chat
     * Respecte SRP et ISP - interface ségrégée pour le formatage
     */
    #[Route('/chat/{id}/messages', name: 'app_chat_messages', methods: ['GET'])]
    public function getMessages(Chat $chat): JsonResponse
    {
        if (!$this->chatManagementService->canUserAccessChat($chat, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        // Utilisation du service de formatage (SRP + DIP)
        $messagesData = array_map(
            fn($message) => $this->dataFormatter->formatMessageForApi($message, $this->getUser()),
            $chat->getMessages()->toArray()
        );

        return $this->json($messagesData);
    }

    /**
     * Envoie un message dans un chat
     * Respecte SRP - une seule responsabilité : envoyer un message
     */
    #[Route('/chat/{id}/send', name: 'app_chat_send_message', methods: ['POST'])]
    public function sendMessage(Chat $chat, Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$this->chatManagementService->canUserAccessChat($chat, $user)) {
            throw $this->createAccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);
        $content = trim($data['content'] ?? '');

        if (empty($content)) {
            return $this->json(['error' => 'Le message ne peut pas être vide'], 400);
        }

        // Délégation au service de chat (SRP)
        $message = $this->chatService->sendMessage($chat, $user, $content);

        // Utilisation du service de formatage (DIP)
        return $this->json($this->dataFormatter->formatMessageForApi($message, $user));
    }

    /**
     * Recherche des utilisateurs
     * Respecte SRP - une seule responsabilité : rechercher des utilisateurs
     */
    #[Route('/api/users/search', name: 'app_users_search', methods: ['GET'])]
    public function searchUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $users = $userRepository->searchByEmail($query, $this->getUser());

        // Utilisation du service de formatage (SRP + DIP)
        $usersData = array_map(
            fn($user) => $this->dataFormatter->formatUserForApi($user),
            $users
        );

        return $this->json($usersData);
    }

    /**
     * Génère un JWT pour Mercure
     * Respecte SRP - une seule responsabilité : générer un JWT
     */
    #[Route('/api/chat/jwt', name: 'app_chat_jwt', methods: ['GET'])]
    public function getChatJWT(): JsonResponse
    {
        $user = $this->getUser();
        $jwt = $this->chatService->generateUserJWT($user);

        return $this->json([
            'jwt' => $jwt,
            'mercure_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3000/.well-known/mercure',
        ]);
    }
}
