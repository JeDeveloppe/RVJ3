<?php

namespace App\Controller;

use App\Entity\ResetPassword;
use App\Form\ResetPasswordType;
use App\Repository\AddressRepository;
use App\Repository\UserRepository;
use App\Repository\ResetPasswordRepository;
use App\Service\PasswordService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Form\EmailForSendResetPasswordType;


class SecurityController extends AbstractController
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        private AddressRepository $addressRepository,
        private UserRepository $userRepository,
        private PasswordService $passwordService,
        private ResetPasswordRepository $resetPasswordRepository
    ) {}

    #[Route(path: '/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $metas['description'] = 'Connectez-vous a votre espace membre pour pouvoir profiter de nos services';


        return $this->render('site/pages/security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error, 'metas' => $metas]);
    }

    #[Route(path: 'logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/delete-user', name: 'delete_user_from_database')]
    public function deleteUserFromDatabase(
        Request $request,
        TokenStorageInterface $tokenStorage
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home');
        }
        // Si l'utilisateur est un administrateur, on refuse la suppression
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $this->addFlash('danger', 'Impossible de supprimer un compte administrateur !');
            return $this->redirectToRoute('app_home'); // Ou vers ta page profil/dashboard
        }

        // 1. On anonymise TOUTES les adresses du profil sans en supprimer une seule
        $addresses = $this->addressRepository->findByUser($user);
        foreach ($addresses as $address) {
            $address->setFirstname('Anonyme');
            $address->setLastname('Anonyme');
            $address->setOrganization(null);
            $address->setStreet('Adresse supprimée (RGPD)');
            // On laisse la ville (City) intacte pour tes statistiques de livraison globales !
        }

        // 2. On anonymise le User
        $user->setEmail('anon_' . $user->getId() . '@compte-supprime.fr');
        $user->setPhone(null);
        $user->setNickname('Utilisateur Désinscrit');
        $user->setPassword(bin2hex(random_bytes(32))); // Sécurité : mot de passe aléatoire incrackable
        $user->setRoles([]);
        $user->setMembership(null);
        $user->setAccountnumber('SUPPRIME');

        // On valide tout en un seul bloc
        $this->em->flush();

        // 3. Déconnexion et message Flash
        $session = $request->getSession();

        /** @var FlashBagInterface $flashBag */
        $flashBag = $session->getBag('flashes');

        // On sauvegarde les flashs existants (dont celui qu'on va ajouter)
        $flashBag->add('success', 'Votre compte utilisateur a bien été supprimé !');
        $backupFlashes = $flashBag->all();

        // 3. Déconnexion sécurisée de Symfony
        $tokenStorage->setToken(null);

        // 4. Invalidation complète de la session
        $session->invalidate();

    // 5. On réinjecte les flashs sauvegardés dans la TOUTE NOUVELLE session
        /** @var FlashBagInterface $newFlashBag */
        $newFlashBag = $request->getSession()->getBag('flashes');
        $newFlashBag->initialize($backupFlashes);

        return $this->redirectToRoute('app_home');
    }

    #[Route('/check-email', name: 'check_email')]
    public function checkEmail(Request $request): Response
    {

        $form = $this->createForm(EmailForSendResetPasswordType::class, null);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $user = $this->userRepository->findOneBy(['email' => $form->get('email')->getData()]);

            if(!$user){

                $form->get('email')->addError(new FormError('Aucun compte n\'est associé à cette adresse email...'));

            }else{

                $resetPassword = new ResetPassword();
                $resetPassword->setEmail($form->get('email')->getData());
                $this->passwordService->saveResetPasswordInDatabaseAndSendEmail($resetPassword);

                $this->addFlash('success', 'Un lien viens de vous être envoyé...');
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('member/email_to_send_link_for_reset_password.html.twig', [
            'emailForSendResetPasswordForm' => $form->createView()
        ]);
    }

    #[Route('/reset-password/{uuid}', name: 'reset_password')]
    public function resetPassword($uuid, Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {

        $resetPassword = $this->resetPasswordRepository->findOneBy(['uuid' => $uuid]);

        if(!$resetPassword OR $resetPassword->isIsUsed() != false){

            $this->addFlash('warning', 'Demande inconnue ou déjà utilisée !');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ResetPasswordType::class, null);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {

            if($form->get('password')->getData() !== $form->get('passwordVerify')->getData()){

                $form->get('password')->addError(new FormError('Les mots de passe ne sont pas identiques...'));

            }else{

                // encode the plain password
                $user = $this->userRepository->findOneBy(['email' => $resetPassword->getEmail()]);
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                    );

                $this->em->persist($user);

                //update invitation
                $resetPassword->setIsUsed(true);

                $this->em->persist($resetPassword);

                $this->em->flush();

                $this->addFlash('success', 'Mot de passe mis à jour !');
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('site/pages/password/reset_password.html.twig', [
            'resetPasswordForm' => $form->createView()
        ]);
    }
}
