<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;

class CatalogControllerService
{
    public function __construct(
        private RouterInterface $routerInterface,
        private Security $security
        ){
    }

    public function pageCatalogue()
    {
        $catalogControllerServiceContent = [
            'header_h1_no_purple'=> 'Nos',
            'header_h1_purple' => 'catalogues',
            'header_description' => 'Nous vous proposons 2 types de catalogue: celui des pièces détachées pour compléter vos jeux et celui des jeux d\'occasions pour vous faire plaisir à petit prix.',
            'dark_button_link' => $this->routerInterface->generate('app_catalogue_occasions'),
            'dark_button_link_archor' => '',
            'dark_button_text' => 'Jeux d\'occasion',
            'yellow_button_link' => $this->routerInterface->generate('app_catalogue_pieces_detachees'),
            'yellow_button_link_archor' => '',
            'yellow_button_text' => 'Pièces détachées',
            'img_asset' => 'prestations/prestation_header.png',
            'img_alt' => 'Image de pièces au détail'
        ];

        return $catalogControllerServiceContent;
    }

}