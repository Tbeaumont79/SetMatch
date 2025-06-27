<?php

namespace App\DataFixtures;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ChatFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer quelques utilisateurs existants
        $userRepository = $manager->getRepository(User::class);
        $users = $userRepository->findAll();

        if (count($users) < 2) {
            return; // Pas assez d'utilisateurs pour créer des chats
        }

        // Créer 3 conversations entre différents utilisateurs
        $conversations = [
            [
                'participants' => [$users[0], $users[1]],
                'messages' => [
                    ['author' => $users[0], 'content' => 'Salut ! Ça te dit de jouer au tennis demain ?'],
                    ['author' => $users[1], 'content' => 'Salut ! Oui avec plaisir, à quelle heure ?'],
                    ['author' => $users[0], 'content' => 'Vers 14h au club central ?'],
                    ['author' => $users[1], 'content' => 'Parfait ! À demain alors 👍'],
                ]
            ],
            [
                'participants' => [$users[0], $users[2] ?? $users[1]],
                'messages' => [
                    ['author' => $users[2] ?? $users[1], 'content' => 'Hey ! J\'ai vu ton post pour le tournoi'],
                    ['author' => $users[0], 'content' => 'Ah super ! Tu veux participer ?'],
                    ['author' => $users[2] ?? $users[1], 'content' => 'Oui carrément ! Comment on s\'inscrit ?'],
                ]
            ]
        ];

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

        foreach ($conversations as $conversationData) {
            $chat = new Chat();

            // Ajouter les participants
            foreach ($conversationData['participants'] as $participant) {
                $chat->addParticipant($participant);
            }

            $manager->persist($chat);

            // Créer les messages avec des heures décalées
            $baseTime = new \DateTimeImmutable('-2 hours');
            foreach ($conversationData['messages'] as $index => $messageData) {
                $message = new Message();
                $message->setContent($messageData['content']);
                $message->setAuthor($messageData['author']);
                $message->setChat($chat);

                // Simuler des messages envoyés à quelques minutes d'intervalle
                $messageTime = $baseTime->modify('+' . ($index * 5) . ' minutes');
                $reflection = new \ReflectionClass($message);
                $property = $reflection->getProperty('created_at');
                $property->setAccessible(true);
                $property->setValue($message, $messageTime);

                $manager->persist($message);
            }

            // Mettre à jour le last_message_at du chat
            $chat->setLastMessageAt($baseTime->modify('+' . (count($conversationData['messages']) * 5) . ' minutes'));
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
