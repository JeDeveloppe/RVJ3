<?php

namespace App\Controller;

use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class SecurityController extends AbstractController
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        private AddressRepository $addressRepository
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
}
