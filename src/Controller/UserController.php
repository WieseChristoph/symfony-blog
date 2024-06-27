<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditUserFormType;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route(name: "app_user_")]
class UserController extends AbstractController
{
    #[Route("/register", name: 'register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, string $avatarDirectory): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatar')->getData();
            if ($avatarFile) {
                $avatarFilename = "avatar_" . uniqid() . "." . $avatarFile->guessExtension();

                try {
                    $avatarFile->move($avatarDirectory, $avatarFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_user_register');
                }
                
                $user->setAvatarFilename($avatarFilename);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_login');
        }

        return $this->render('user/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route("/login", name: 'login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: "/user/{id}/edit", name: "edit", methods: ["GET", "POST"], requirements: ['id' => '\d+'])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function edit(Request $request, User $user, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, string $avatarDirectory): Response
    {
        /** @var User $authUser */
        $authUser = $this->getUser();
        if ($authUser !== $user && !$authUser->isAdmin())
            throw $this->createAccessDeniedException();

        $form = $this->createForm(EditUserFormType::class, $user, ['show_is_admin' => $authUser->isAdmin(), 'is_admin' => $user->isAdmin()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );
            }

            if ($authUser->isAdmin()) {
                $user->setAdmin($form->get('isAdmin')->getData());
            }

            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatar')->getData();
            if ($form->get('deleteAvatar')->getData()) {
                try {
                    $user->deleteAvatar($avatarDirectory);
                } catch (IOException $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                }
            } else if ($avatarFile) {
                try {
                    $user->saveAvatar($avatarDirectory, $avatarFile);
                } catch (FileException $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                }
            }

            $entityManager->flush();

            return  $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'userEditForm' => $form,
            'user' => $user
        ]);
    }


    #[Route("/user/{id}/delete", name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted("ROLE_ADMIN")]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('user_delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }
        
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    #[Route("/makeMeAdmin", name: 'makemeadmin', methods: ['GET'])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function makeMeAdmin(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setAdmin(true);

        $entityManager->flush();

        return $this->redirectToRoute("app_user_login");
    }

    #[Route("/makeMeUser", name: 'makemeuser', methods: ['GET'])]
    #[IsGranted("IS_AUTHENTICATED")]
    public function makeMeUser(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setAdmin(false);

        $entityManager->flush();

        return $this->redirectToRoute("app_user_login");
    }
}
