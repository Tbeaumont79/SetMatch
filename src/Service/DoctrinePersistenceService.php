<?php

namespace App\Service;

use App\Contract\PersistenceInterface;
use Doctrine\ORM\EntityManagerInterface;


class DoctrinePersistenceService implements PersistenceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}


    public function persist(object $entity): void
    {
        $this->entityManager->persist($entity);
    }


    public function flush(): void
    {
        $this->entityManager->flush();
    }


    public function persistAndFlush(object $entity): void
    {
        $this->persist($entity);
        $this->flush();
    }
}
