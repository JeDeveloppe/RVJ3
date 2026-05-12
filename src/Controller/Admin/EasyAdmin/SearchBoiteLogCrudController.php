<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\SearchBoiteLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

class SearchBoiteLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SearchBoiteLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Log de recherche')
            ->setEntityLabelInPlural('Logs de recherche')
            // On trie par défaut du plus récent au plus ancien
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(50);
    }

    public function configureActions(Actions $actions): Actions
    {
        // On empêche l'édition ou la création manuelle (ce sont des logs)
        return $actions
            ->disable(Action::NEW, Action::EDIT);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnIndex();
        
        yield DateTimeField::new('createdAt', 'Date et Heure')
            ->setFormat('dd/MM/yyyy HH:mm');

        yield TextField::new('query', 'Mot-clé recherché')
            ->setHelp('Ce que l\'utilisateur a tapé dans la barre de recherche');

        // Utilisation d'un Badge pour voir d'un coup d'oeil si c'est vide
        // yield IntegerField::new('resultsCount', 'Résultats trouvés')
        //     ->setTemplatePath('admin/fields/search_count_badge.html.twig'); 
            // Alternative simple si tu ne veux pas de template custom :
        yield IntegerField::new('resultsCount', 'Résultats');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('resultsCount', 'Filtrer par nombre de résultats'));
    }
}