<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;

//?Le Profiler de dev (barre d'outils) capture une "photo" complete (VarDumper Cloner) de tout ce qui
//?est touche pendant la requete (requetes Doctrine, entites, session...). Sur les pages admin lourdes
//?(fiches avec beaucoup de relations/ventes liees), cette capture peut a elle seule epuiser la memoire
//?PHP, meme quand la page elle-meme fonctionnerait sans probleme. On desactive donc le profiler
//?uniquement sur /admin, ou son utilite (debug visuel) compte moins que la stabilite.
class DisableProfilerOnAdminSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ?Profiler $profiler = null,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->profiler) {
            return;
        }

        if (str_starts_with($event->getRequest()->getPathInfo(), '/admin')) {
            $this->profiler->disable();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
