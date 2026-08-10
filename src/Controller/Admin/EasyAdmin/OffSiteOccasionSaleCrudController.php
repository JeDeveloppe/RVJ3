<?php

namespace App\Controller\Admin\EasyAdmin;

use DateTimeImmutable;
use App\Entity\Payment;
use App\Entity\Delivery;
use App\Entity\Occasion;
use Doctrine\ORM\QueryBuilder;
use App\Service\DocumentService;
use App\Repository\TaxRepository;
use App\Entity\OffSiteOccasionSale;
use App\Repository\StockRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\DocumentStatusRepository;
use App\Repository\ShippingMethodRepository;
use App\Repository\DocumentParametreRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Component\HttpFoundation\RequestStack;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class OffSiteOccasionSaleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OffSiteOccasionSale::class;
    }

    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private TaxRepository $taxRepository,
        private ShippingMethodRepository $shippingMethodRepository,
        private DocumentStatusRepository $documentStatusRepository,
        private DocumentParametreRepository $documentParametreRepository,
        private PaymentRepository $paymentRepository,
        private DocumentService $documentService,
        private StockRepository $stockRepository
    )
    {
    }

    //?Remplace Request::get() (deprecated depuis symfony/http-foundation 7.4) : meme ordre de
    //?repli (attributes puis query puis request).
    private function getRequestParam(string $key): mixed
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request->attributes->get($key) ?? $request->query->get($key) ?? $request->request->get($key);
    }

    public function configureFields(string $pageName): iterable
    {
        if($this->getRequestParam('entityId')){
            $disabled = true;
            $occasionField = AssociationField::new('occasion')->setLabel('Occasion')
            ->setFormTypeOptions(['attr' => ['placeholder' => 'Sélectionner...']])
            ->setDisabled($disabled);
        }else{
            $disabled = false;

            if($this->getRequestParam('crudAction') == 'new'){

                $occasionField = AssociationField::new('occasion')->setLabel('Occasion (peut être en réservé)')
                                    ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                                    ->setQueryBuilder(
                                        fn(QueryBuilder $queryBuilder) => 
                                        $queryBuilder
                                            ->where('entity.isOnline = :true')
                                            //TODO René
                                            ->orWhere('entity.reserve IS NOT NULL')
                                            ->setParameter('true', true)
                                            ->orderBy('entity.reference', 'ASC')
                                    )->setDisabled($disabled)->onlyOnForms()->setColumns(6);
            }else{
                $occasionField = AssociationField::new('occasion')->setLabel('Occasion')->setDisabled($disabled)->onlyOnIndex();
            }
        }

        return [
            FormField::addTab('Détails')->setPermission('ROLE_ADMIN'),
                $occasionField,
                MoneyField::new('movementPrice')->setStoredAsCents()->setCurrency('EUR')
                    ->setLabel('Prix du mouvement (HT)')
                    ->setTextAlign('center')->setDisabled($disabled)->setColumns(6),
                    
                AssociationField::new('movement')
                    ->setLabel('Mouvement:')->setColumns(6)
                    ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                    ->setDisabled($disabled)->onlyWhenCreating(),
                AssociationField::new('meansOfPaiement')
                    ->setLabel('Moyen de paiement')->setColumns(6)
                    ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                    ->setQueryBuilder(
                        fn(QueryBuilder $queryBuilder) => 
                        $queryBuilder
                            ->orderBy('entity.name', 'ASC')
                    )
                    ->setDisabled($disabled)->onlyWhenCreating(),

                AssociationField::new('movement')
                    ->setLabel('Mouvement:')->setColumns(6)
                    ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                    ->setDisabled($disabled)->renderAsEmbeddedForm()->onlyOnIndex(),
                AssociationField::new('meansOfPaiement')
                    ->setLabel('Moyen de paiement')->setColumns(6)
                    ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                    ->setDisabled($disabled)->renderAsEmbeddedForm()->onlyOnIndex(),
                //?autocomplete() obligatoire : la table user fait 5800+ lignes, sans ca
                //?EasyAdmin les rend toutes en options inline dans le formulaire.
                AssociationField::new('user')
                    ->setLabel('Acheteur')
                    ->setQueryBuilder(
                        fn(QueryBuilder $queryBuilder) =>
                        $queryBuilder
                        ->orderBy('entity.email', 'ASC')
                    )
                    ->setFormTypeOption('choice_label', function($item) {
                        return $item->getAccountNumber().' # '.$item->getEmail();
                    })
                    ->autocomplete()
                    ->setDisabled($disabled)
                    ->onlyOnForms()->setColumns(6),

            FormField::addTab('Détails')->onlyWhenUpdating()->setPermission('ROLE_ADMIN'),
                DateTimeField::new('createdAt')->setLabel('Saisie le')->setFormat('dd-MM-yyyy')->setDisabled(true)->onlyWhenUpdating(),
                AssociationField::new('createdBy')->setLabel('Saisie par')->onlyWhenUpdating()->setDisabled(true),

        ];
    }


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des ventes/dons rapide d\'occasions:')
            ->setPageTitle('new', 'Vente rapide d\'un occasion')
            ->setPageTitle('edit', 'Édition d\'un mouvement occasion')
            ->setPageTitle('detail', 'Détails d\'un mouvement occasion')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'occasion.reference'])
            ->setEntityLabelInSingular('Nouveau mouvement')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
        
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('movement')
        ;
    }
    
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if($entityInstance instanceof OffSiteOccasionSale) {
            $now = new DateTimeImmutable('now');
            $entityInstance->setPlaceOfTransaction('Vente emportée')->setMovementTime($now);

            $billingAddress = 'CLIENT(E) de passage';
            $deliveryAddress = $entityInstance->getPlaceOfTransaction();
            $details = [];
            $details['panier_boites'] = ['init' => 'init']; // for action in admin
            $details['totalPanier'] = $entityInstance->getMovementPrice();
            $details['tax'] = $this->taxRepository->findOneBy([]);
            $details['deliveryCostWithoutTax'] = new Delivery();
            $details['deliveryCostWithoutTax']->setPriceExcludingTax(0);
            $details['preparationHt'] = 0;
            $details['shipping'] = $this->shippingMethodRepository->findOneBy(['name' => 'RETRAIT PENDANT UNE FOIRE']);
            $details['totauxOccasions']['weigth'] = $entityInstance->getOccasion()->getBoite()->getWeigth();
            $details['totauxOccasions']['price'] = $entityInstance->getMovementPrice();
            $details['panier_occasions'] = [];
            $details['panier_boites'] = [];
            $details['panier_items'] = [];
            $details['occasion'] = $entityInstance->getOccasion();

            //on genere un nouveau document
            $this->documentService->generateDocumentInDatabaseFromSaleDuringAfair($details,$billingAddress,$deliveryAddress,$entityInstance);

            $user = $this->security->getUser();
            $entityInstance->setCreatedAt($now)->setCreatedBy($user);
            $entityManager->persist($entityInstance);

            $occasion = $entityInstance->getOccasion();

            $reserve = $occasion->getReserve();
            if($reserve){
                $reserve->removeOccasionDuringFair($occasion);
            }

            $occasion->setOffSiteOccasionSale($entityInstance);
            $entityManager->persist($occasion);

            $entityManager->flush();
        }

    }

}
