<?php

namespace App\EventListener;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Post::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Post::class)]
final class PostEventListener
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function postPersist(Post $post): void
    {
        $this->publishPostUpdate($post, 'created');
    }

    public function postUpdate(Post $post): void
    {
        $this->publishPostUpdate($post, 'updated');
    }

    private function publishPostUpdate(Post $post, string $action): void
    {
        try {
            $violations = $this->validator->validate($post);
            if ($violations->count() > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()][] = $violation->getMessage();
                }
                $this->logger->warning('Post invalide détecté, publication Mercure annulée', [
                    'post_id' => $post->getId(),
                    'validation_errors' => $errors
                ]);
                return;
            }

            if (!$post->getAuthor()) {
                $this->logger->warning('Post sans auteur détecté, publication Mercure annulée', [
                    'post_id' => $post->getId()
                ]);
                return;
            }

            $postData = [
                'action' => $action,
                'id' => $post->getId(),
                'content' => $post->getContent(),
                'image' => $post->getImage(),
                'created_at' => $post->getCreatedAt()?->format('c'),
                'updated_at' => $post->getUpdatedAt()?->format('c'),
                'author' => [
                    'id' => $post->getAuthor()->getId(),
                    'email' => $post->getAuthor()->getEmail(),
                    'username' => explode('@', $post->getAuthor()->getEmail())[0],
                    'avatar' => $post->getAuthor()->getAvatar(),
                ],
                'timestamp' => (new \DateTimeImmutable())->format('c')
            ];

            $jsonData = $this->serializer->serialize($postData, 'json');

            $update = new Update(
                'posts',
                $jsonData,
                false,
                null,
                'application/json'
            );

            $this->hub->publish($update);

            $this->logger->info('Post publié vers Mercure avec succès', [
                'post_id' => $post->getId(),
                'action' => $action,
                'author_id' => $post->getAuthor()->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la publication Mercure', [
                'post_id' => $post->getId(),
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
