<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/post/{post_id}/comment', name: "app_comment_", requirements: ['post_id' => '\d+'])]
#[IsGranted("IS_AUTHENTICATED")]
class CommentController extends AbstractController
{
    #[Route('/new', name: 'new', methods: ['POST'])]
    public function new(Request $request, #[MapEntity(mapping: ['post_id' => 'id'])] Post $post, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('comment_new'.$post->getId().$user->getId(), $request->getPayload()->getString('_token'))) {
            $comment = new Comment();
            $comment->setMessage($request->request->get("message"));
            $comment->setPost($post);
            $comment->setUser($this->getUser());

            $entityManager->persist($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post_show', ["id" => $post->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($comment->getUser() !== $user && !$user->isAdmin())
            throw $this->createAccessDeniedException();

        if ($this->isCsrfTokenValid('comment_delete'.$comment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }
}
