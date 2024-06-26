<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/dashboard", name: "app_dashboard_")]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/users', name: 'users', methods: ['GET'])]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('dashboard/users.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/posts', name: 'posts', methods: ['GET'])]
    public function posts(PostRepository $postRepository): Response
    {
        return $this->render('dashboard/posts.html.twig', [
            'posts' => $postRepository->findAll(),
        ]);
    }

    #[Route('/comments', name: 'comments', methods: ['GET'])]
    public function comments(CommentRepository $commentRepository): Response
    {
        return $this->render('dashboard/comments.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }
}
