<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Service\MailService;
use App\Entity\VoucherDiscount;
use App\Service\VoucherDiscountService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use Symfony\Bundle\SecurityBundle\Security;

class VoucherDiscountCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return VoucherDiscount::class;
    }

    public function __construct(
        private VoucherDiscountService $voucherDiscountService
    )
    {
    }
    
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('email')->setLabel('Email d\'envoi qui devrait être l\'utilisateur:')->onlyOnForms(),
            TextField::new('email')->setLabel('Destinataire:')->onlyOnIndex(),
            TextField::new('email')->setLabel('Destinataire:')->onlyOnDetail(),
            DateTimeField::new('endOfTheCollect')->setLabel('Date de la collecte:'),
            TextField::new('token')->onlyOnDetail()->setLabel('Token:')->setDisabled(true),
            IntegerField::new('numberOfKilosCollected')->setLabel('Nombre de kilos collectés (arrondi):')->onlyOnForms(),
            IntegerField::new('numberOfKilosCollected')->setLabel('Nombre de kilos collectés (arrondi):')->onlyOnDetail(),
            MoneyField::new('discountValueExcludingTax')->setLabel('Montant de la remise:')->setCurrency('EUR')->setStoredAsCents(),
            MoneyField::new('remainingValueToUseExcludingTax')->setLabel('Montant restant à utiliser:')->hideOnForm()->setCurrency('EUR')->setStoredAsCents(),
            DateTimeField::new('validUntil')->setLabel('Valide jusqu\'au:'),
            DateTimeField::new('createdAt')->setFormat('dd.MM.yyyy à HH:mm')->setLabel('Créé / envoyé:')->onlyOnDetail()->setDisabled(true),
            BooleanField::new('isUsed')->hideWhenCreating()->setLabel('Utilisé:')->setDisabled(true),
            TextField::new('createdBy')->onlyOnDetail()->setDisabled(true),
            CollectionField::new('documents')->onlyOnDetail()
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des bons d\'achat')
            ->setPageTitle('new', 'Nouveau bon d\'achat')
            ->setPageTitle('edit', 'Édition d\'un bon d\'achat')
            ->setDefaultSort(['id' => 'DESC'])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            //?Valeur monetaire : reserve aux admins, pas aux benevoles (qui ont ROLE_BENEVOLE,
            //?suffisant par defaut pour acceder a tout /admin sans cette restriction explicite).
            ->setPermission(Action::INDEX, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN');

    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if($entityInstance instanceof VoucherDiscount) {

        $this->voucherDiscountService->saveVoucherDiscountInDatabaseAndSendEmail($entityInstance);

        }
    }
}
