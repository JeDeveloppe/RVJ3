<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\MeansOfPayement;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class MeansOfPayementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MeansOfPayement::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            AssociationField::new('payments')->setLabel('Paiements')->onlyOnIndex(),
            //?CollectionField retire (ex: onlyOnForms) : un moyen de paiement peut etre lie a
            //?des milliers de paiements (ex: 2523 pour la carte bancaire) - EasyAdmin
            //?reconstruit la config de champs complete pour chaque ligne, meme bug memoire que
            //?BoiteCrudController::documentLines / UserCrudController::documents.
        ];
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des moyens de paiement')
            ->setPageTitle('new', 'Nouveau moyen de paiement')
            ->setPageTitle('edit', 'Édition d\'un moyen de paiement')
            ->setDefaultSort(['name' => 'ASC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            //?Donnees financieres : reserve aux admins, pas aux benevoles (qui ont ROLE_BENEVOLE,
            //?suffisant par defaut pour acceder a tout /admin sans cette restriction explicite).
            ->setPermission(Action::INDEX, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN');

    }
}
