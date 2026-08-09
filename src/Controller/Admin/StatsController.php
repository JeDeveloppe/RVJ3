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
        //?meme jeu, cf. "Cochon qui rit"/"Docteur Maboul") : classement principal sur le total cumule
        //?toutes editions confondues, avec le detail par edition disponible (accordeon) sans etre fondu
        //?dans le total individuel de chaque ligne.
        //?Pas de limite serree ici : le total par groupe doit couvrir TOUTES les editions d'un jeu,
        //?pas seulement les X plus vendues individuellement (sinon le total sous-estimerait les jeux
        //?avec beaucoup d'editions a faible volume chacune).
        $rows = $this->boiteRepository->findBoitesWithMostArticlesSold(5000);
        $groups = [];
        foreach ($rows as $row) {
            $name = $row['name'];
            if (!isset($groups[$name])) {
                $groups[$name] = ['name' => $name, 'total' => 0, 'editions' => []];
            }
            $groups[$name]['total'] += $row['totalQuantitySold'];
            $groups[$name]['editions'][] = $row;
        }
        $groups = array_values($groups);
        usort($groups, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $this->render('admin/stats/index.html.twig', [
            'bestSellingItems' => $this->itemRepository->findBestSellingItems(20),
            'averages' => $this->boiteRepository->findAverageWeightAndPrice(),
            'bestSellingBoitesGrouped' => array_slice($groups, 0, 20),
        ]);
    }
}
