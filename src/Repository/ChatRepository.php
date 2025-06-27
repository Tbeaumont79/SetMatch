<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chat>
 */
class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    /**
     * @return Chat[] Returns an array of Chat objects for a specific participant
     */
    public function findByParticipant(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->andWhere('p = :user')
            ->setParameter('user', $user)
            ->orderBy('c.last_message_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find an existing chat between two users
     */
    public function findExistingChat(User $user1, User $user2): ?Chat
    {
        // Requête plus simple et plus fiable
        $chats = $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->andWhere('p IN (:users)')
            ->andWhere('SIZE(c.participants) = 2')
            ->setParameter('users', [$user1, $user2])
            ->getQuery()
            ->getResult();

        // Vérifier manuellement que les deux utilisateurs sont présents
        foreach ($chats as $chat) {
            $participants = $chat->getParticipants()->toArray();
            if (in_array($user1, $participants) && in_array($user2, $participants)) {
                return $chat;
            }
        }

        return null;
    }

    //    /**
    //     * @return Chat[] Returns an array of Chat objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Chat
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
