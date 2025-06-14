<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Controller\Admin\AdminController;
use App\Entity\QuoteRequest;
use App\Form\QuoteRequestLineType;
use DoctrineExtensions\Query\Mysql\Date;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use Symfony\Component\BrowserKit\Request;
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
            DateField::new('createdAt','Date de demande')->setFormat('dd.MM.yyyy')->setTimezone('Europe/Paris')->setDisabled(true),
            DateField::new('validUntil','Fin de validité')->setFormat('dd.MM.yyyy')->setTimezone('Europe/Paris')->setDisabled(true)->onlyOnIndex(),
            BooleanField::new('isSendByEmail','Envoyer par mail')->setDisabled(true),
            DateField::new('sendByEmailAt','Date d\'envoi')->setFormat('dd.MM.yyyy')->setTimezone('Europe/Paris'),
            CollectionField::new('quoteRequestLines', 'Lignes de devis')->onlyOnIndex()->setDisabled(true),
            // CollectionField::new('quoteRequestLines', 'Lignes de devis')->setEntryType(QuoteRequestLineType::class)->setTemplatePath('admin/fields/quoteRequest_lines.html.twig')->onlyWhenUpdating(),
            MoneyField::new('totalPrice', 'Total')->setDisabled(true)->setCurrency('EUR')->setStoredAsCents(),
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
        ;
    }

    public function configureActions(Actions $actions): Actions
    {

        $sendEmail = Action::new('sendEmail', 'Envoyer par mail', 'fa-solid fa-envelope')
                ->addCssClass('bg-success text-white')
                ->displayAsButton()
                ->linkToRoute('admin_send_quote_to_customer', static function (QuoteRequest $quoteRequest) {
                    return [
                        'quoteRequestId' => $quoteRequest->getId(),
                    ];
                })
                ;

        return $actions
            // ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
            //     return $action
            //         ->setIcon('fa fa-pencil')
            //         ->linkToRoute('admin_quote_request_detail', ['id' => $this->requestStack->getCurrentRequest()->get('entityId')]);
            // })
            ->add(Crud::PAGE_EDIT, $sendEmail)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
        ;
    }

}
