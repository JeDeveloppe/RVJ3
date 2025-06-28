<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\QuoteRequest;
use Dom\Text;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RequestStack;

class QuoteRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return QuoteRequest::class;
    }

    public function __construct(
        private RequestStack $requestStack
    )
    {
        
    }
    public function configureFields(string $pageName): iterable
    {

        return [
            DateField::new('createdAt','Date de la demande')->setFormat('dd.MM.yyyy')->setTimezone('Europe/Paris')->setDisabled(true)->setColumns(3),
            TextField::new('uuid')->setLabel('Token:')->setDisabled(true)->setColumns(3)->onlyOnForms(),
            TextField::new('number','N° de la demande')->setDisabled(true)->setColumns(3),
            DateField::new('document.endOfQuoteValidation','Fin de validité du devis')->setFormat('dd.MM.yyyy')->setTimezone('Europe/Paris')->setDisabled(true)->onlyOnIndex()->setColumns(3),
            BooleanField::new('isSendByEmail','Envoyer par mail')->setDisabled(true)->setColumns(3),
            DateField::new('sendByEmailAt','Date d\'envoi')->setFormat('dd.MM.yyyy')->setTimezone('Europe/Paris')->setColumns(3)->setDisabled(true),
            MoneyField::new('totalPrice', 'Total des pièces demandées')->setDisabled(true)->setCurrency('EUR')->setStoredAsCents()->onlyOnDetail(),
            AssociationField::new('document', 'Document')->renderAsEmbeddedForm(),
            AssociationField::new('quoteRequestStatus', 'Statut')->setDisabled(true),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des demandes de devis')
            ->setPageTitle('new', 'Nouvelle demande de devis')
            ->setPageTitle('edit', 'Édition d\'une demande de devis')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['uuid', 'user.email', 'document.quoteNumber', 'number'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {

        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action
                    ->setIcon('fa fa-pencil')
                    ->displayIf(fn (QuoteRequest $quoteRequest): bool => $quoteRequest->getQuoteRequestStatus()->getLevel() == 2)
                    ->linkToRoute('admin_manual_quote_request_details', function (QuoteRequest $quoteRequest): array {
                        return [
                            'quoteRequestId' => $quoteRequest->getId(),
                        ];
                    })
                    ;
            })
            // ->add(Crud::PAGE_EDIT, $sendEmail)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
        ;
    }

}
