<?php

namespace App\Controller\Admin;

use App\Repository\SearchBoiteLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

//?2 pages admin distinctes (pas 1 seule avec 2 nuages empiles) : les recherches
//?s'accumulent avec le temps, une page unique deviendrait vite trop longue a parcourir.
class SearchBoiteLogController extends AbstractController
{
    public function __construct(
        private SearchBoiteLogRepository $searchBoiteLogRepository,
    ) {
    }

    #[AdminRoute('/recherches-jeux', name: 'search_boite_log_jeux')]
    public function jeux(): Response
    {
        return $this->render('admin/search_boite_log/jeux.html.twig', [
            'recherches' => $this->searchBoiteLogRepository->findGroupedFailedSearches('jeu'),
        ]);
    }

    #[AdminRoute('/recherches-pieces', name: 'search_boite_log_pieces')]
    public function pieces(): Response
    {
        return $this->render('admin/search_boite_log/pieces.html.twig', [
            'recherches' => $this->searchBoiteLogRepository->findGroupedFailedSearches('piece'),
        ]);
    }
}
