<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\ShippingMethod;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class ShippingMethodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ShippingMethod::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setLabel('Nom'),
            ChoiceField::new('price')->setLabel('Payant ou gratuit ?')->setChoices(['GRATUIT' => 'GRATUIT', 'PAYANT' => 'PAYANT']),
            BooleanField::new('isActivedInCart')->setLabel('Actif panier')->renderAsSwitch(),
            BooleanField::new('forOccasionOnly')->setLabel('Actif occasions')->renderAsSwitch(),
            BooleanField::new('isActivedInQuoteRequest')->setLabel('Actif structures adhérentes')->renderAsSwitch(),
            AssociationField::new('documents')->setLabel('Nombre de doc')->onlyOnIndex(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des moyens de retrait/envoi')
            ->setPageTitle('new', 'Nouveau retrait/envoi')
            ->setPageTitle('edit', 'Édition du retrait/envoi')
            ->setDefaultSort(['name' => 'ASC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN');
        
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if($entityInstance instanceof ShippingMethod) {
            $entityInstance->setName(strtoupper($entityInstance->getName()));
            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if($entityInstance instanceof ShippingMethod) {
            $entityInstance->setName(strtoupper($entityInstance->getName()));
            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }
}
