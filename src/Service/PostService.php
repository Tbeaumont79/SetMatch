<?php

namespace App\Service;

use App\Contract\PersistenceInterface;
use App\Contract\ValidatorInterface as AppValidatorInterface;
use App\Entity\Post;
use App\Entity\User;
use App\Exception\PostException;
use App\Repository\PostRepository;


class PostService
{
    public function __construct(
        private readonly PersistenceInterface $persistenceService,
        private readonly PostRepository $postRepository,
        private readonly AppValidatorInterface $validationService,
        private readonly int $defaultLimit = 10
    ) {}


    public function createPost(string $content, User $author, ?string $image = null): Post
    {
        if (!$author) {
            throw PostException::authorRequired();
        }

        $post = new Post();
        $post->setContent($content);
        $post->setAuthor($author);

        if ($image) {
            $post->setImage($image);
        }

        // Délégation de la validation au service spécialisé (DIP)
        $this->validationService->validateEntity($post);

        $this->persistenceService->persist($post);

        return $post;
    }


    public function getRecentPosts(?int $limit = null): array
    {
        $limit = $limit ?? $this->defaultLimit;
        return $this->postRepository->findRecentPostsWithAuthors($limit);
    }


    public function getPostsByUser(int $userId, ?int $limit = null): array
    {
        $limit = $limit ?? $this->defaultLimit;
        return $this->postRepository->findPostsByAuthor($userId, $limit);
    }


    public function getPostsByUserEntity(User $user, ?int $limit = null): array
    {
        return $this->getPostsByUser($user->getId(), $limit);
    }


    public function findPostById(int $id): Post
    {
        $post = $this->postRepository->find($id);

        if (!$post) {
            throw PostException::postNotFound($id);
        }

        return $post;
    }


    public function searchPosts(string $searchTerm, ?int $limit = null): array
    {
        $limit = $limit ?? $this->defaultLimit;
        return $this->postRepository->searchPosts($searchTerm, $limit);
    }


    public function savePostWithValidation(Post $post): void
    {
        // Utilisation du service de validation (DIP + SRP)
        $this->validationService->validateEntity($post, ['service_validation']);
        $this->persistenceService->persist($post);
    }


    public function savePost(Post $post): void
    {
        $this->persistenceService->persistAndFlush($post);
    }


    public function flush(): void
    {
        $this->persistenceService->flush();
    }
}
