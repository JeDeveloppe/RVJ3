<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AddressCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Address::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            BooleanField::new('isFacturation')->setLabel('Facturation')->setDisabled(true)->onlyOnIndex(),
            BooleanField::new('isFacturation','Adresse de facturation ? (cocher)')->onlyOnForms(),
            TextField::new('organization')->setLabel('Organisation'),
            TextField::new('lastname')->setLabel('Nom'),
            TextField::new('firstname')->setLabel('Prénom'),
            TextField::new('street')->setLabel('Adresse'),
            AssociationField::new('city')->setLabel('Ville')->autocomplete(),
            AssociationField::new('user')->setLabel('Client')->setDisabled(true)->onlyOnIndex(),
            //?autocomplete() obligatoire : sans ca, EasyAdmin rend les 5800+ users en options
            //?inline dans un simple <select> (pas de N+1 via __toString ici, User::__toString()
            //?est deja bon marche, mais 5800+ entites hydratees + rendues en HTML reste tres
            //?lourd en soi).
            AssociationField::new('user','Quel client ?')->onlyWhenCreating()->autocomplete(),

        ];
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des adresses')
            ->setPageTitle('new', 'Nouvelle adresse')
            ->setPageTitle('edit', 'Édition de l\'adresse')
            ->setDefaultSort(['lastname' => 'ASC'])
            ->setSearchFields(['city.name','city.postalcode','street','organization','firstname','lastname','user.email'])

        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN');
        
    }
}
