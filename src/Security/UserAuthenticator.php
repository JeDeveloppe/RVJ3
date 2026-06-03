<?php

namespace App\Security;

use App\Repository\PanierRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class UserAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManagerInterface,
        private PanierRepository $panierRepository,
        )
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        // 1. On cherche si l'utilisateur existe en BDD avant de valider
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            // 2. S'il n'existe pas, on lève l'exception avec TON message personnalisé
            throw new UserNotFoundException('Utilisateur inconnu.');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {


        //? On recupere l'utilisateur
        $user = $this->userRepository->findOneBy(['email' => $request->getSession()->get(Security::LAST_USERNAME)]);

        if($user){
            //on met a jour le visiteur
            $user->setLastvisite(new DateTimeImmutable('now'));
            $this->entityManagerInterface->persist($user);

            //on transforme les paniers au nouveau visiteur loguer
            $paniers = $this->panierRepository->findBy(['tokenSession' => $request->getSession()->get('tokenSession')]);
            foreach($paniers as $panier){
                $panier->setUser($user);
                $this->entityManagerInterface->persist($panier);
            }

            $this->entityManagerInterface->flush();

        }

        $panierInSession = $request->getSession()->get('paniers', []);
        if(array_key_exists('back_url_after_login', $panierInSession)){

            $route = $panierInSession['back_url_after_login'];
            $request->getSession()->remove($panierInSession['back_url_after_login']);

        }else{

            $route = 'app_home';
        }

        return new RedirectResponse($this->urlGenerator->generate($route));

        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
