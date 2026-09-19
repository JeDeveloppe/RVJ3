<?php

namespace App\Controller\Admin;

use App\Repository\SearchBoiteLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

class SearchBoiteLogController extends AbstractController
{
    public function __construct(
        private SearchBoiteLogRepository $searchBoiteLogRepository,
    ) {
    }

    #[AdminRoute('/recherches', name: 'search_boite_log')]
    public function index(): Response
    {
        //?Le catalogue ne propose plus que la recherche par jeu : seules ces lignes comptent
        //?(les anciennes lignes 'piece'/'inconnu' finissent purgees par deleteOldLogs).
        return $this->render('admin/search_boite_log/index.html.twig', [
            'recherches' => $this->searchBoiteLogRepository->findGroupedFailedSearches('jeu'),
        ]);
    }
}
