<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class HistoryService
{
    private const HISTORY_MAX = 1;

    public function __construct(
        private RequestStack $requestStack,
        ){
    }


    public function saveHistoryLogic(): void
    {
        $session = $this->requestStack->getSession();
        $history = $session->get('history', []);

        $currentPath = $this->requestStack->getCurrentRequest()->getPathInfo();
        $currentRoute = $this->requestStack->getCurrentRequest()->get('_route');
        $currentParams = $this->requestStack->getCurrentRequest()->query->all();

        $history[] = [
            'path' => $currentPath,
            'route' => $currentRoute,
            'params' => $currentParams
        ];

        if(count($history) > self::HISTORY_MAX){
            array_shift($history);
        }
        $session->set('history', $history);
    }

}