<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Exception\PostException;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PostService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PostRepository $postRepository,
        private readonly ValidatorInterface $validator,
        private readonly int $defaultLimit = 10
    ) {
    }

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
        $violations = $this->validator->validate($post);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw PostException::invalidContent(implode(', ', $errors));
        }
        $this->entityManager->persist($post);

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
        $violations = $this->validator->validate($post, null, ['service_validation']);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw PostException::invalidContent(implode(', ', $errors));
        }

        $this->entityManager->persist($post);
    }

    public function savePost(Post $post): void
    {
        $this->entityManager->persist($post);
        $this->entityManager->flush();
    }


    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
