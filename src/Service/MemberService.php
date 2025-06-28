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
            'role_min' => 'ROLE_USER',
            'title' => 'Mes commandes',
            'imgName' => 'commandes.svg',
            'link' => $this->routerInterface->generate('member_historique')
        ];
        $themes[] = [
            'role_min' => 'ROLE_STRUCTURE_ADHERENTE',
            'title' => 'Mes demandes de devis',
            'imgName' => 'devis.png',
            'link' => $this->routerInterface->generate('member_historique_devis')
        ];
        $themes[] = [
            'role_min' => 'ROLE_USER',
            'title' => 'Mes adresses',
            'imgName' => 'adresses.svg',
            'link' => $this->routerInterface->generate('member_adresses')           
        ];
        $themes[] = [
            'role_min' => 'ROLE_USER',
            'title' => 'Mes paramètres',
            'imgName' => 'parametres.svg',
            'link' => $this->routerInterface->generate('member_compte')           
        ];

        return $themes;
    }
}