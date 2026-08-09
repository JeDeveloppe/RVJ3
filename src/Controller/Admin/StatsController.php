<?php

namespace App\Controller\Admin;

use App\Repository\ItemRepository;
use App\Repository\BoiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

class StatsController extends AbstractController
{
    public function __construct(
        private ItemRepository $itemRepository,
        private BoiteRepository $boiteRepository,
    ) {
    }

    #[AdminRoute('/stats', name: 'stats')]
    public function index(): Response
    {
        //?Regroupement par nom (plusieurs boites peuvent partager le meme nom - editions differentes du
        //?meme jeu, cf. "Cochon qui rit"/"Docteur Maboul") : PAS de total cumule entre editions. Verifie
        //?sur "Docteur Maboul" : 5 editions affichaient exactement le meme chiffre (348) car elles
        //?partagent EXACTEMENT les memes articles (item_boite identique) - chaque piece vendue est donc
        //?deja comptee 5 fois avant meme de sommer. Additionner ces totaux deja gonfles aurait multiplie
        //?l'erreur au lieu de la corriger. On classe les groupes par leur meilleure edition individuelle,
        //?chaque edition gardant son propre chiffre, sans aucune addition entre elles.
        $rows = $this->boiteRepository->findBoitesWithMostArticlesSold(5000);
        $groups = [];
        foreach ($rows as $row) {
            $name = $row['name'];
            if (!isset($groups[$name])) {
                $groups[$name] = ['name' => $name, 'editions' => [], 'bestEditionQuantity' => 0];
            }
            $groups[$name]['editions'][] = $row;
            $groups[$name]['bestEditionQuantity'] = max($groups[$name]['bestEditionQuantity'], $row['totalQuantitySold']);
        }
        $groups = array_values($groups);
        usort($groups, fn ($a, $b) => $b['bestEditionQuantity'] <=> $a['bestEditionQuantity']);

        return $this->render('admin/stats/index.html.twig', [
            'bestSellingItems' => $this->itemRepository->findBestSellingItems(20),
            'averages' => $this->boiteRepository->findAverageWeightAndPrice(),
            'bestSellingBoitesGrouped' => array_slice($groups, 0, 20),
        ]);
    }
}
