<?php

namespace App\Controller\Site;

use App\Entity\User;
use DateTimeImmutable;
use App\Form\RegistrationFormType;
use App\Security\UserAuthenticator;
use App\Service\UserService;
use App\Service\UtilitiesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;


class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        UserAuthenticator $authenticator,
        EntityManagerInterface $entityManager,
        UtilitiesService $utilitiesService): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if($form->get('plainPassword')->getData() != $form->get('plainPasswordVerification')->getData()){
            $form->addError(new FormError('Les mots de passe ne correspondent pas !'));
        }

        if($form->isSubmitted() && $form->isValid()){

            // encode the plain password
            $user->setCreatedAt(new DateTimeImmutable('now'))
            ->setLastvisite(new DateTimeImmutable('now'))
            ->setRoles(['ROLE_USER'])
            ->setAccountnumber('init')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            // $userService->transformPaniersCreatedWhitoutUserToNewUserLogged($user);


            $user->setAccountnumber($utilitiesService->generateAccountNumber($user->getId()));
            $entityManager->persist($user);
            $entityManager->flush();

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('site/pages/registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
