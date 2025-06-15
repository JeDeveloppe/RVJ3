<?php

namespace App\EventSubscriber;

use App\Repository\PanierRepository;
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
        private ShippingMethodRepository $shippingMethodRepository
    )
    {
    }

    public function onControllerEvent(ControllerEvent $event): void
    {

        $siteSetting = $this->siteSettingRepository->findOneBy([]);
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

        $panier_boites = 0;
        foreach($paniers as $panier){
            if($panier->getBoite() != null){
                $panier_boites += 1;
            }
        }

        $this->twig->addGlobal('marquee', $siteSetting->getMarquee());
        $this->twig->addGlobal('fairDay', $siteSetting->getFairday());
        $this->twig->addGlobal('twigEvent_paniers', count($paniers));
        $this->twig->addGlobal('twigEvent_panier_boites', $panier_boites);
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
