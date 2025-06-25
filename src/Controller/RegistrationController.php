<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('staff@setmatch.com', 'staff'))
                    ->to((string) $user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );


            $security->login($user, 'form_login', 'main');

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Un email de vérification vous a été envoyé.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, VerifyEmailHelperInterface $verifyEmailHelper, EntityManagerInterface $entityManager): Response
    {
        try {
            $users = $entityManager->getRepository(User::class)->findBy(['isVerified' => false]);

            $validatedUser = null;
            foreach ($users as $testUser) {
                try {
                    $verifyEmailHelper->validateEmailConfirmationFromRequest(
                        $request,
                        (string) $testUser->getId(),
                        (string) $testUser->getEmail()
                    );
                    $validatedUser = $testUser;
                    break;
                } catch (VerifyEmailExceptionInterface $e) {
                    continue;
                }
            }

            if (!$validatedUser) {
                throw new VerifyEmailExceptionInterface('Aucun utilisateur correspondant trouvé pour ce lien de vérification');
            }

            $validatedUser->setIsVerified(true);
            $entityManager->persist($validatedUser);
            $entityManager->flush();

            $this->addFlash('success', 'Votre email a été vérifié avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');

        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', 'Lien de vérification invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }
    }
}
