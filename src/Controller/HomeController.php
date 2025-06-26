<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Service\PostService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly PostService $postService
    ) {
    }

    #[Route('/home', name: 'app_home')]
    public function index(Request $request): Response
    {
        $form = null;

        if ($this->getUser()) {
            $post = new Post();
            $form = $this->createForm(PostType::class, $post);
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $post->setAuthor($this->getUser());

                if ($form->isValid()) {
                    $this->postService->savePostWithValidation($post);
                    $this->postService->flush();

                    $this->addFlash('success', 'Votre post a été publié avec succès !');
                    return $this->redirectToRoute('app_home');
                }
            }
        }

        $posts = $this->postService->getRecentPosts(10);

        return $this->render('home/index.html.twig', [
            'posts' => $posts,
            'form' => $form?->createView(),
        ]);
    }
}
