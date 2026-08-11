<?php

namespace App\EventSubscriber;

use App\Entity\QuoteRequest;
use App\Repository\PanierRepository;
use App\Repository\QuoteRequestLineRepository;
use App\Repository\ShippingMethodRepository;
use Twig\Environment;
use App\Repository\SiteSettingRepository;
use App\Service\PanierService;
use App\Service\UtilitiesService;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class TwigEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Environment $twig,
        private SiteSettingRepository $siteSettingRepository,
        private RequestStack $requestStack,
        private Security $security,
        private UtilitiesService $utilitiesService,
        private PanierRepository $panierRepository,
        private PanierService $panierService,
        private ShippingMethodRepository $shippingMethodRepository,
        private QuoteRequestLineRepository $quoteRequestLineRepository
    )
    {
    }

    public function onControllerEvent(ControllerEvent $event): void
    {
        //?Panier/session/marquee n'ont aucun sens sur le back-office (EasyAdmin) : on evite cette
        //?logique inutile sur chaque page admin (charge site setting, paniers, generation de token...).
        //?On se base sur le namespace du controleur reellement execute, PAS sur le chemin de la
        //?requete : EasyAdminBundle::AdminRouterSubscriber (priorite 128, s'execute avant nous)
        //?substitue le controleur pour les liens de menu type linkToRoute() (ex: le lien "SITE",
        //?/admin?routeName=app_home) SANS changer le pathinfo de la requete (qui reste "/admin").
        //?Se baser sur le chemin faisait donc passer a tort de vraies pages du site (avec navbar/
        //?panier, ex: la page d'accueil ouverte depuis le menu admin) pour des pages d'admin.
        $controller = $event->getController();
        $controllerObject = is_array($controller) ? $controller[0] : $controller;
        if (is_object($controllerObject) && str_starts_with($controllerObject::class, 'App\\Controller\\Admin\\')) {
            return;
        }

        $siteSetting = $this->siteSettingRepository->find(1);
        $session = $this->requestStack->getSession();
        $tokenSession = $session->get('tokenSession');
        //par default livraison a Caen
        
        if(!$tokenSession){
            
            $pre_token = $this->utilitiesService->generateRandomString(200);
            $now = new DateTimeImmutable('now');
            $milli = (int) $now->format('Uv');
            $token = $pre_token.'_'.$milli;
            $session->set('tokenSession', $token);
        }

        $panierInSession = $session->get('paniers', []);
        if(!$panierInSession){

            $panierInSession = [];
            $panierInSession['voucherDiscountId'] = NULL;
            $session->set('paniers', $panierInSession);
        }

        $paniers = $this->panierService->returnAllPaniersFromUser();

        $this->twig->addGlobal('marquee', $siteSetting->getMarquee());
        $this->twig->addGlobal('fairDay', $siteSetting->getFairday());
        $this->twig->addGlobal('twigEvent_paniers', count($paniers));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ControllerEvent::class => 'onControllerEvent',
        ];
    }

    public function maintenanceRedirection(RequestEvent $event) {

        $event->setResponse(
            new Response($this->twig->render('site/maintenance/index.html.twig'), Response::HTTP_SERVICE_UNAVAILABLE)
        );
        $event->stopPropagation();
        
    }
}
