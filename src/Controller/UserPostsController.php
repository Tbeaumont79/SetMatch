<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;

final class UserPostsController extends AbstractController
{
    #[Route('/user/posts', name: 'app_user_posts')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $userPost = $entityManager->getRepository(Post::class)->findBy(['author' => $this->getUser()]);

        return $this->render('user_posts/index.html.twig', [
            'controller_name' => 'UserPostsController',
            'userPost' => $userPost
        ]);
    }
}
