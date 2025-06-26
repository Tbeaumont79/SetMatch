<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Trouve les posts récents avec leurs auteurs (optimisé avec JOIN)
     */
    public function findRecentPostsWithAuthors(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.author', 'u')
            ->addSelect('u')
            ->orderBy('p.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les posts d'un auteur spécifique
     */
    public function findPostsByAuthor(int $authorId, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.author = :authorId')
            ->setParameter('authorId', $authorId)
            ->orderBy('p.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans le contenu des posts
     */
    public function searchPosts(string $searchTerm, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.author', 'u')
            ->addSelect('u')
            ->where('p.content LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('p.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les posts par auteur
     */
    public function countPostsByAuthor(int $authorId): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.author = :authorId')
            ->setParameter('authorId', $authorId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les posts avec images uniquement
     */
    public function findPostsWithImages(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.author', 'u')
            ->addSelect('u')
            ->where('p.image IS NOT NULL')
            ->orderBy('p.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Post[] Returns an array of Post objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Post
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
