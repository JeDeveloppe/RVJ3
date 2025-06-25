<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use App\Repository\DocumentStatusRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Component\HttpFoundation\RequestStack;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class DocumentCrudController extends AbstractCrudController
{
    public function __construct(
        private RequestStack $requestStack,
        private DocumentRepository $documentRepository,
        private DocumentStatusRepository $documentStatusRepository
    )
    {
    }

    public static function getEntityFqcn(): string
    {
        return Document::class;
    }

    public function configureFields(string $pageName): iterable
    {

        //?edition logic
        $id = $this->requestStack->getCurrentRequest()->get('entityId');
        if($id){
            $document = $this->documentRepository->find($id);
            if($document->getDocumentStatus() == $this->documentStatusRepository->findOneBy(['action' => 'END'])){
                $disabled = true;
            }else{
                $disabled = false;
            }
        }else{
            $disabled = false;
        }

        return [
            FormField::addTab('Général'),
            TextField::new('quoteNumber')->setLabel('Num. devis')->setDisabled(true)->setColumns(6)->setTextAlign('center'),
            TextField::new('billNumber')->setLabel('Num. facture')->setDisabled(true)->setColumns(6)->setTextAlign('center'),
            DateTimeField::new('endOfQuoteValidation')
                ->setLabel('Valable jusqu\'au:')
                ->setDisabled(true)
                ->setFormat('dd.MM.yyyy à HH:mm:ss'),
            BooleanField::new('isLastQuote','Dernier devis')->setDisabled(true)->setColumns(6)->setTextAlign('center'),
            MoneyField::new('documentLineTotals.itemsPriceWithoutTax')
                ->setLabel('Prix des articles')
                ->setDisabled(true)
                ->setCurrency('EUR')
                ->setStoredAsCents()
                ->hideOnIndex()->setTextAlign('center'),
            MoneyField::new('documentLineTotals.occasionsPriceWithoutTax')
                ->setLabel('Prix des occasions')
                ->setDisabled(true)
                ->setCurrency('EUR')
                ->setStoredAsCents()->hideOnIndex()->setTextAlign('center'),
            MoneyField::new('documentLineTotals.discountonpurchase')->setLabel('Remise de quantité')
                ->setDisabled(true)
                ->setCurrency('EUR')
                ->hideOnIndex()
                ->setStoredAsCents()->setTextAlign('center'),
            AssociationField::new('taxRate')
                ->setLabel('Taux de tva')
                ->setDisabled(true)
                ->hideOnIndex()
                ->renderAsEmbeddedForm()->setColumns(6)->setTextAlign('center'),
            MoneyField::new('cost')
                ->setLabel('Frais HT')
                ->setDisabled(true)
                ->setCurrency('EUR')
                ->setStoredAsCents()
                ->hideOnIndex()->setColumns(6)->setTextAlign('center'),
            MoneyField::new('totalExcludingTax')
                ->setLabel('Total HT')
                ->setDisabled(true)
                ->setCurrency('EUR')
                ->setStoredAsCents()
                ->hideOnIndex()->setColumns(6)->setTextAlign('center'),
            MoneyField::new('deliveryPriceExcludingTax')
                ->setLabel('Frais de port HT')
                ->setCurrency('EUR')
                ->setStoredAsCents()
                ->setDisabled(true)->setColumns(6)->setTextAlign('center'),
            MoneyField::new('totalWithTax')
                ->setLabel('Total TTC')
                ->setDisabled(true)
                ->setCurrency('EUR')
                ->setStoredAsCents()->setColumns(6)->setTextAlign('center'),
            AssociationField::new('payment')->renderAsEmbeddedForm()->setLabel('Paiement')->setDisabled(true)->onlyOnIndex()->setColumns(6)->setTextAlign('center'),

            FormField::addTab('Totaux / vente'),
            // IntegerField::new('documentLineTotals.boitesWeigth')->setLabel('Poid des boites'),
            // IntegerField::new('documentLineTotals.boitesPriceWithoutTax')->setLabel('Prix des boites'),
            IntegerField::new('documentLineTotals.itemsWeigth')->setLabel('Poid des articles (en g)')->hideOnIndex(),
            MoneyField::new('documentLineTotals.itemsPriceWithoutTax')
                ->setLabel('Prix des articles')
                ->setCurrency('EUR')
                ->setStoredAsCents()
                ->hideOnIndex(),
            IntegerField::new('documentLineTotals.occasionsWeigth')->setLabel('Poid des occasions (en g)')->hideOnIndex(),
            MoneyField::new('documentLineTotals.occasionsPriceWithoutTax')
                ->setLabel('Prix des occasions')
                ->setCurrency('EUR')
                ->setStoredAsCents()
                ->hideOnIndex(),
            IntegerField::new('documentLineTotals.boitesWeigth')->setLabel('Poid des pièces envoyées (en g)')->hideOnIndex(),
            MoneyField::new('documentLineTotals.boitesPriceWithoutTax')
                ->setLabel('Prix des pièces envoyées')
                ->setCurrency('EUR')
                ->setStoredAsCents()
                ->hideOnIndex(),
            MoneyField::new('documentLineTotals.voucherDiscountValueUsed')->setLabel('Bon d\'achat')
                ->setDisabled(true)
                ->setCurrency('EUR')
                ->hideOnIndex()
                ->setStoredAsCents(),


            FormField::addTab('Suivi / Communication'),
            DateTimeField::new('createdAt')
            ->setLabel('Devis créé le')
            ->setDisabled(true)
            ->hideOnIndex()
            ->setFormat('dd.MM.yyyy à HH:mm:ss')->setColumns(6),
            DateTimeField::new('timeOfSendingQuote')
            ->setLabel('Email le')
            ->setDisabled(true)
            ->hideOnIndex()
            ->setFormat('dd.MM.yyyy à HH:mm:ss')->setColumns(6),
            DateTimeField::new('endOfQuoteValidation')
            ->setLabel('Fin de validation du devis')
            ->setDisabled(true)
            ->hideOnIndex()
            ->setFormat('dd.MM.yyyy à HH:mm:ss')->setColumns(6),
            BooleanField::new('isQuoteReminder')
                ->setLabel('Devis relancer')
                ->setDisabled(true)
                ->hideOnIndex()->setColumns(6),
            BooleanField::new('isDeleteByUser')
                ->setLabel('Supprimer par l\'utilisateur')
                ->setDisabled(true)
                ->hideOnIndex()->setColumns(6),
            TextField::new('message')
                ->setLabel('Message')
                ->setDisabled(true)
                ->hideOnIndex()->setColumns(12),


            FormField::addTab('Adresses / Envoi ou retrait'),
            TextField::new('deliveryAddress')
                ->renderAsHtml()
                ->setLabel('Adresse de livraison')
                ->setDisabled(true)
                ->hideOnIndex()->setColumns(12),
            TextField::new('billingAddress')
                ->setLabel('Adresse de facturation')
                ->renderAsHtml()
                ->setDisabled(true)
                ->hideOnIndex()->setColumns(12),
                
            TextField::new('shippingMethod')
                ->setLabel('Envoi / retrait:')
                ->hideOnIndex()->setDisabled(true),
            DateTimeField::new('sendingAt')
                ->setLabel('Marchandise envoyée le:')
                ->setDisabled(true)
                ->setFormat('dd.MM.yyyy à HH:mm:ss'),
            // AssociationField::new('documentsending.sendingAt')
            //     ->setLabel('Status du document')
            //     ->renderAsEmbeddedForm()->onlyOnIndex(),
            // AssociationField::new('documentsending.sendingAt')
            //     ->setLabel('Status du document')
            //     ->renderAsEmbeddedForm()->onlyOnDetail(),

            FormField::addTab('Détails de la vente / Status du document'),
            AssociationField::new('documentStatus')
            ->setLabel('Status du document')->renderAsEmbeddedForm()->hideOnIndex(),
            CollectionField::new('documentLines')->setTemplatePath('admin/fields/documentLines.html.twig')->setDisabled(true)->hideOnIndex(),

            FormField::addTab('Paiement'),
            AssociationField::new('payment')
                ->setLabel('Paiement')
                ->setDisabled(true)->hideOnIndex()->renderAsEmbeddedForm(),
            DateTimeField::new('payment.timeOfTransaction')->setLabel('Date de paiement:')->setDisabled(true)->hideOnIndex(),
            AssociationField::new('user')
                ->setLabel('Client')
                ->setDisabled(true)->hideOnIndex(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des documents')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['quoteNumber', 'billNumber', 'user.email', 'token','id'])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('billNumber')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $id = $this->requestStack->getCurrentRequest()->get('entityId');

        if($id){
            $document = $this->documentRepository->find($id);
            $toPrint = Action::new('print', 'Imprimer', 'fa fa-print')->linkToRoute('download_billing_document', ['tokenDocument' => $document->getToken()])->setHtmlAttributes(['target' => '_blank']);
        
            return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_DETAIL, $toPrint)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);  

        }else{
            return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action
                    ->setIcon('fa fa-pencil')
                    // Vous pouvez ajouter une condition displayIf ici si nécessaire, comme pour QuoteRequest
                    ->displayIf(fn (Document $document): bool => $document->getBillNumber() == null)
                    ->linkToRoute('admin_manual_document_details', function (Document $document): array {
                        return [
                            'documentId' => $document->getId(),
                        ];
                    })
                    ;
            })
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL); 
        }

      
    }
}