<?php

namespace App\EventListener;

use App\Entity\Post;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

final class PostEventListener implements EventSubscriber
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Post) {
            return;
        }

        $this->publishPostUpdate($entity, 'created');
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Post) {
            return;
        }

        $this->publishPostUpdate($entity, 'updated');
    }

    private function publishPostUpdate(Post $post, string $action): void
    {
        try {
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
