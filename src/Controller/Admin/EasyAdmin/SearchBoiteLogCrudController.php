<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\SearchBoiteLog;
use App\Repository\SearchBoiteLogRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\HttpFoundation\Response;

//?Pas de liste/edition/creation classique ici : ce sont de simples logs (recherches sans
//?resultat dans le catalogue). La seule vue utile est le nuage de mots regroupant les
//?recherches par frequence, pour reperer les jeux demandes mais absents/pas encore en ligne.
class SearchBoiteLogCrudController extends AbstractCrudController
{
    public function __construct(
        private SearchBoiteLogRepository $searchBoiteLogRepository,
    ) {}

    public static function getEntityFqcn(): string
    {
        return SearchBoiteLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Recherche sans résultat')
            ->setEntityLabelInPlural('Recherches sans résultat');
    }

    public function index(AdminContext $context): KeyValueStore|Response
    {
        return $this->render('admin/search_boite_log/regroupees.html.twig', [
            'recherches' => $this->searchBoiteLogRepository->findGroupedFailedSearches(),
        ]);
    }
}
