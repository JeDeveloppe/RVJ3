<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Payment;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PaymentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('timeOfTransaction')->setLabel('Date de paiement')->setFormat('dd.MM.yyyy à HH:mm:ss')->setTextAlign('center'),
            AssociationField::new('document')->setLabel('Document (n° devis)')
                ->onlyOnForms()
                ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                ->setQueryBuilder(
                        fn(QueryBuilder $queryBuilder) => 
                        $queryBuilder
                        ->where('entity.payment IS NULL')
                        ->orderBy('entity.quoteNumber', 'ASC')
                    )->setTextAlign('center'),
            AssociationField::new('document')->setLabel('Document (n° facture)')->hideOnForm()->setTextAlign('center'),
            AssociationField::new('meansOfPayment')
                ->setLabel('Status paiement')
                ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                ->renderAsEmbeddedForm()->setTextAlign('center'),
            TextField::new('tokenPayment')
                ->setLabel('Token de paiement')
                ->setFormTypeOptions(['attr' => ['value' => 'RefaitesVosJeuxManuel']])
                ->setDisabled(true)
                ->hideOnIndex(),
            TextField::new('details')->setLabel('Détail:')->setTextAlign('center'),
            DateTimeField::new('createdAt')->setLabel('Enregistré le')->setFormat('dd.MM.yyyy à HH:mm:ss')->onlyOnDetail(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des paiements')
            ->setPageTitle('new', 'Nouveau paiement')
            ->setPageTitle('edit', 'Édition d\'un paiement')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            //?Donnees financieres : reserve aux admins, pas aux benevoles (qui ont ROLE_BENEVOLE,
            //?suffisant par defaut pour acceder a tout /admin sans cette restriction explicite).
            ->setPermission(Action::INDEX, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN');

    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('meansOfPayment')
        ;
    }
}
