<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setAuthor($this->getUser());

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Votre post a été publié avec succès !');
            return $this->redirectToRoute('app_home');
        }

        $posts = $entityManager->getRepository(Post::class)
            ->findBy([], ['created_at' => 'DESC'], 10);

        return $this->render('home/index.html.twig', [
            'posts' => $posts,
            'form' => $form->createView(),
        ]);
    }
}
