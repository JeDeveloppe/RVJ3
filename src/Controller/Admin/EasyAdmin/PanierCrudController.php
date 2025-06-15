<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Panier;
use App\Service\PanierService;
use Doctrine\ORM\Mapping\Id;
use phpDocumentor\Reflection\Types\Integer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PanierCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Panier::class;
    }

    public function __construct(
        private PanierService $panierService
    )
    {}

    public function configureFields(string $pageName): iterable
    {
        
        //suppression des paniers > x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

        return [
            IdField::new('id','Ligne n°'),
            DateField::new('createdAt','Fin de validité')->setFormat('dd.MM.yyyy à HH:mm:ss'),
            // MoneyField::new('priceWithoutTax')->setCurrency('EUR')->setStoredAsCents(),
            // IntegerField::new('qte'),
            MoneyField::new('unitPriceExclusingTax')->setCurrency('EUR')->setStoredAsCents()->onlyOnDetail(),
            AssociationField::new('boite'),
            AssociationField::new('occasion'),
            AssociationField::new('item'),
            AssociationField::new('user'),
            TextField::new('tokenSession')->setDisabled(true),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des paniers en cours')
            ->setPageTitle('detail', 'Détails d\'une ligne de panier')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields([])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->remove(Crud::PAGE_DETAIL, Action::EDIT);        
    }

    public function delete(AdminContext $context)
    {
        $panier = $context->getEntity()->getInstance();

        $this->panierService->deleteCartLineRealtime($panier->getId());

        return parent::delete($context);

    }
}