<?php

namespace App\Controller;

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

#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    #[Route('/chat', name: 'app_chat_list', methods: ['GET'])]
    public function list(ChatRepository $chatRepository): Response
    {
        $user = $this->getUser();
        $chats = $chatRepository->findByParticipant($user);

        return $this->render('chat/list.html.twig', [
            'chats' => $chats,
        ]);
    }

    #[Route('/chat/start', name: 'app_chat_start', methods: ['POST'])]
    public function startChat(Request $request, UserRepository $userRepository, ChatRepository $chatRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $participantId = $data['participant_id'] ?? null;

        if (!$participantId) {
            return $this->json(['error' => 'ID du participant requis'], 400);
        }

        $participant = $userRepository->find($participantId);
        if (!$participant) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }

        if ($participant === $user) {
            return $this->json(['error' => 'Vous ne pouvez pas démarrer une conversation avec vous-même'], 400);
        }

        // Vérifier si une conversation existe déjà
        $existingChat = $chatRepository->findExistingChat($user, $participant);
        if ($existingChat) {
            return $this->json(['chat_id' => $existingChat->getId()]);
        }

        // Créer une nouvelle conversation
        $chat = new Chat();
        $chat->addParticipant($user);
        $chat->addParticipant($participant);

        $entityManager->persist($chat);
        $entityManager->flush();

        return $this->json(['chat_id' => $chat->getId()]);
    }

    #[Route('/chat/{id}', name: 'app_chat_show', methods: ['GET'])]
    public function show(Chat $chat): Response
    {
        $user = $this->getUser();

        if (!$chat->hasParticipant($user)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette conversation.');
        }

        return $this->render('chat/show.html.twig', [
            'chat' => $chat,
        ]);
    }

    #[Route('/chat/{id}/messages', name: 'app_chat_messages', methods: ['GET'])]
    public function getMessages(Chat $chat): JsonResponse
    {
        $user = $this->getUser();

        if (!$chat->hasParticipant($user)) {
            throw $this->createAccessDeniedException();
        }

        $messages = $chat->getMessages();
        $messagesData = [];

        foreach ($messages as $message) {
            $messagesData[] = [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'author' => [
                    'id' => $message->getAuthor()->getId(),
                    'email' => $message->getAuthor()->getEmail(),
                ],
                'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                'is_mine' => $message->getAuthor() === $user,
            ];
        }

        return $this->json($messagesData);
    }

    #[Route('/chat/{id}/send', name: 'app_chat_send_message', methods: ['POST'])]
    public function sendMessage(Chat $chat, Request $request, ChatService $chatService): JsonResponse
    {
        $user = $this->getUser();

        if (!$chatService->canUserAccessChat($chat, $user)) {
            throw $this->createAccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);
        $content = trim($data['content'] ?? '');

        if (empty($content)) {
            return $this->json(['error' => 'Le message ne peut pas être vide'], 400);
        }

        // Utiliser le service ChatService qui gère Mercure automatiquement
        $message = $chatService->sendMessage($chat, $user, $content);

        // Retourner les données formatées
        return $this->json($chatService->formatMessageForUser($message, $user));
    }

    #[Route('/api/users/search', name: 'app_users_search', methods: ['GET'])]
    public function searchUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $currentUser = $this->getUser();

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $users = $userRepository->searchByEmail($query, $currentUser);
        $usersData = [];

        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'display_name' => explode('@', $user->getEmail())[0],
            ];
        }

        return $this->json($usersData);
    }

    #[Route('/api/chat/jwt', name: 'app_chat_jwt', methods: ['GET'])]
    public function getChatJWT(ChatService $chatService): JsonResponse
    {
        $user = $this->getUser();
        $jwt = $chatService->generateUserJWT($user);

        return $this->json([
            'jwt' => $jwt,
            'mercure_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3000/.well-known/mercure',
        ]);
    }
}
