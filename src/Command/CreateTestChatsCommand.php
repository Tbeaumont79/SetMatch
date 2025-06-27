<?php

namespace App\Command;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-chats',
    description: 'Create some test chats for development',
)]
class CreateTestChatsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer les utilisateurs existants
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findAll();

        if (count($users) < 2) {
            $io->error('Pas assez d\'utilisateurs trouvés. Veuillez d\'abord créer des utilisateurs.');
            return Command::FAILURE;
        }

        // Vérifier si des chats existent déjà
        $chatRepository = $this->entityManager->getRepository(Chat::class);
        $existingChats = $chatRepository->findAll();

        if (count($existingChats) > 0) {
            $io->warning('Des chats existent déjà. Suppression pour recréer...');
            foreach ($existingChats as $chat) {
                $this->entityManager->remove($chat);
            }
            $this->entityManager->flush();
        }

        $io->title('Création de chats de test');

        // Créer les conversations
        $conversations = [
            [
                'participants' => [$users[0], $users[1]],
                'messages' => [
                    ['author' => $users[0], 'content' => 'Salut ! Ça te dit de jouer au tennis demain ?'],
                    ['author' => $users[1], 'content' => 'Salut ! Oui avec plaisir, à quelle heure ?'],
                    ['author' => $users[0], 'content' => 'Vers 14h au club central ?'],
                    ['author' => $users[1], 'content' => 'Parfait ! À demain alors 👍'],
                ]
            ]
        ];

        if (count($users) >= 3) {
            $conversations[] = [
                'participants' => [$users[0], $users[2]],
                'messages' => [
                    ['author' => $users[2], 'content' => 'Hey ! J\'ai vu ton post pour le tournoi'],
                    ['author' => $users[0], 'content' => 'Ah super ! Tu veux participer ?'],
                    ['author' => $users[2], 'content' => 'Oui carrément ! Comment on s\'inscrit ?'],
                ]
            ];
        }

        if (count($users) >= 4) {
            $conversations[] = [
                'participants' => [$users[1], $users[3]],
                'messages' => [
                    ['author' => $users[1], 'content' => 'Salut ! Tu cherches un partenaire de double ?'],
                    ['author' => $users[3], 'content' => 'Oui exactement ! Tu as de l\'expérience ?'],
                    ['author' => $users[1], 'content' => 'J\'ai un niveau intermédiaire, ça te va ?'],
                    ['author' => $users[3], 'content' => 'Parfait ! On se fait un match ce weekend ?'],
                    ['author' => $users[1], 'content' => 'OK pour samedi matin !'],
                ]
            ];
        }

        $createdChats = 0;

        foreach ($conversations as $conversationData) {
            $chat = new Chat();

            // Ajouter les participants
            foreach ($conversationData['participants'] as $participant) {
                $chat->addParticipant($participant);
            }

            $this->entityManager->persist($chat);

            // Créer les messages avec des heures décalées
            $baseTime = new \DateTimeImmutable('-2 hours');
            foreach ($conversationData['messages'] as $index => $messageData) {
                $message = new Message();
                $message->setContent($messageData['content']);
                $message->setAuthor($messageData['author']);
                $message->setChat($chat);

                // Simuler des messages envoyés à quelques minutes d'intervalle
                $messageTime = $baseTime->modify('+' . ($index * 5) . ' minutes');

                // Utiliser la réflexion pour modifier la date de création
                $reflection = new \ReflectionClass($message);
                $property = $reflection->getProperty('created_at');
                $property->setAccessible(true);
                $property->setValue($message, $messageTime);

                $this->entityManager->persist($message);
            }

            // Mettre à jour le last_message_at du chat
            $lastMessageTime = $baseTime->modify('+' . ((count($conversationData['messages']) - 1) * 5) . ' minutes');
            $chat->setLastMessageAt($lastMessageTime);

            $createdChats++;

            $participants = array_map(function($user) {
                return $user->getEmail();
            }, $conversationData['participants']);

            $io->writeln(sprintf(
                'Chat créé entre: %s (%d messages)',
                implode(' et ', $participants),
                count($conversationData['messages'])
            ));
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d chats de test créés avec succès !', $createdChats));

        return Command::SUCCESS;
    }
}
