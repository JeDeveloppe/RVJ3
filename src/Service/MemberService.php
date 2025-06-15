<?php

namespace App\Service;

use Symfony\Component\Routing\RouterInterface;

class MemberService
{
    public function __construct(
        private RouterInterface $routerInterface
    )
    {
        
    }
    public function memberThemes(){

        $themes[] = [
            'title' => 'Mes commandes',
            'imgName' => 'commandes.svg',
            'link' => $this->routerInterface->generate('member_historique')
        ];
        $themes[] = [
            'title' => 'Mes demandes de devis',
            'imgName' => 'devis.png',
            'link' => $this->routerInterface->generate('member_historique_devis')
        ];
        $themes[] = [
            'title' => 'Mes adresses',
            'imgName' => 'adresses.svg',
            'link' => $this->routerInterface->generate('member_adresses')           
        ];
        $themes[] = [
            'title' => 'Mes paramètres',
            'imgName' => 'parametres.svg',
            'link' => $this->routerInterface->generate('member_compte')           
        ];

        return $themes;
    }
}