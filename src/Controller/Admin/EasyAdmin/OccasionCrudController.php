<?php

namespace App\Controller\Admin\EasyAdmin;

use DateTime;
use DateTimeImmutable;
use App\Entity\Occasion;
use PharIo\Manifest\Url;
use Doctrine\ORM\QueryBuilder;
use App\Service\UtilitiesService;
use App\Repository\BoiteRepository;
use App\Repository\OccasionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\HttpFoundation\RequestStack;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;

class OccasionCrudController extends AbstractCrudController
{   
    public static function getEntityFqcn(): string
    {
        return Occasion::class;
    }

    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
        private OccasionRepository $occasionRepository,
        private UtilitiesService $utilitiesService,
        private BoiteRepository $boiteRepository,
        private AdminUrlGenerator $adminUrlGenerator
    )
    { 
    }


    public function configureFields(string $pageName): iterable
    {

        $disabledIfBilled = true;
        $disabledIfBilled = $this->utilitiesService->easyAdminLogicWhenBilling($this->requestStack);
        $boiteShell = $this->requestStack->getCurrentRequest()->get('boiteShell');

        if($boiteShell && $this->requestStack->getCurrentRequest()->get('crudAction') == 'new'){

            $this->getContext()->getEntity()->getInstance()->setBoite($this->boiteRepository->find($boiteShell));

        }

        $adminView = $this->isGranted('ROLE_ADMIN');
        $benevoleView = $this->isGranted('ROLE_BENEVOLE');

        if($adminView){
            return [


                FormField::addTab('Fiche de l\'occasion')->setPermission('ROLE_ADMIN'),
                    FormField::addFieldset('Actions / Pramètres'),
                    TextField::new('reference')->setLabel('Référence')->setDisabled(true)->onlyOnIndex()->setTextAlign('center')->setColumns(4),
                    BooleanField::new('isOnline')->setLabel('En ligne')->setColumns(6)->onlyOnForms()->setDisabled($disabledIfBilled)->setColumns(4),
                    BooleanField::new('isOnline')->setLabel('En ligne')->setColumns(6)->onlyOnIndex()->setDisabled(true),
                    BooleanField::new('isReserved')->setLabel('Réservé')->setColumns(6)->onlyOnIndex()->setDisabled(true),
                    BooleanField::new('isBilled')->setLabel('Vendu / donner')->setColumns(6)->onlyOnIndex()->setDisabled(true),
                    AssociationField::new('stock','Visible dans le stock:')->setColumns(4)->setDisabled($disabledIfBilled)->renderAsEmbeddedForm()->onlyOnDetail(),
                    AssociationField::new('stock','Visible dans le stock:')->setColumns(4)->setDisabled($disabledIfBilled)->onlyOnForms(),
                    
                    FormField::addFieldset('Détails'),
                    ImageField::new('boite.image')
                        ->setBasePath($this->getParameter('app.path.boites_images'))
                        ->setLabel('Image')
                        ->onlyOnIndex()
                        ->setPermission('ROLE_BENEVOLE'),
                    TextField::new('reference')->setLabel('Référence')->setDisabled(true)->onlyWhenUpdating()->setTextAlign('center')->setColumns(4),
                    AssociationField::new('boite')
                        ->setLabel('Dépend de la boite')
                        ->setFormTypeOptions(
                            [
                                'placeholder' => 'Sélectionner...',
                            ]
                            )
                        ->setQueryBuilder(
                            fn(QueryBuilder $queryBuilder) => 
                            $queryBuilder
                            ->where('entity.isOccasion = :value')
                            ->setParameter('value', true)
                            ->orderBy('entity.id', 'ASC')
                        )
                        ->setDisabled(true)
                        ->setColumns(10),
                    BooleanField::new('isNew')
                        ->setLabel('Neuf')
                        ->onlyOnIndex()
                        ->setDisabled(true)
                        ->setColumns(2),
                    TextField::new('boxEquipnmentAndRulesConditions')
                        ->setLabel('État boite / materiel / Régle du jeu')
                        ->onlyOnIndex()
                        ->setTextAlign('center'),
                    AssociationField::new('boxCondition')
                        ->setLabel('État de la boite')
                        ->autocomplete()
                        ->onlyOnForms()
                        ->setDisabled($disabledIfBilled)
                        ->setColumns(4),
                    AssociationField::new('equipmentCondition')
                        ->autocomplete()
                        ->setLabel('État du matériel')
                        ->onlyOnForms()
                        ->setDisabled($disabledIfBilled)
                        ->setColumns(4),
                    AssociationField::new('gameRule')
                        ->setLabel('Régle du jeu')->autocomplete()
                        ->onlyOnForms()
                        ->setDisabled($disabledIfBilled)
                        ->setColumns(4),
                    TextField::new('information')
                        ->setLabel('Information sur l\'occasion')
                        ->setDisabled($disabledIfBilled)
                        ->onlyOnForms()->setColumns(12),

                    FormField::addFieldset('Prix'),
                    BooleanField::new('isNew')
                        ->setLabel('Jeu neuf')
                        ->onlyOnForms()
                        ->setDisabled($disabledIfBilled)
                        ->setColumns(12),
                    MoneyField::new('boite.htPrice')
                        ->setLabel('Prix HT de référence (boite):')
                        ->setDisabled(true)
                        ->setStoredAsCents()
                        ->setCurrency('EUR')
                        ->onlyWhenUpdating()->setColumns(4)->setTextAlign('center'),
                    MoneyField::new('boiteHtPrice','Prix HT de réference (boite)')->onlyWhenCreating()->setCurrency('EUR')->setDisabled(true)->setColumns(4)->setTextAlign('center')->setStoredAsCents(),
                    MoneyField::new('virtualPriceWithoutTax','Prix de référence différent de la boite')->onlyWhenCreating()->setCurrency('EUR')->setColumns(4)->setTextAlign('center')->setStoredAsCents(),
                    MoneyField::new('priceWithoutTax')
                        ->setLabel('Prix de vente HT:')
                        ->setStoredAsCents()
                        ->setCurrency('EUR')
                        ->onlyWhenUpdating()
                        ->setDisabled($disabledIfBilled)->setColumns(4)->setTextAlign('center'),
                    // MoneyField::new('discountedPriceWithoutTax')
                    //     ->setLabel('Prix remisé HT:')
                    //     ->setStoredAsCents()
                    //     ->setCurrency('EUR')
                    //     ->onlyWhenUpdating()
                    //     ->setDisabled($disabledIfBilled)->setColumns(4)->setTextAlign('center'),
                    TextField::new('priceWithoutTaxAndDiscountedPriceWithoutTax', 'Prix normal / remisé')->onlyOnIndex()->setTextAlign('center'),

                    FormField::addTab('Informations stocks')->onlyWhenUpdating(),
                        AssociationField::new('paniers')
                            ->setLabel('Panier en cours:')
                            ->setDisabled(true)
                            ->setTextAlign('center')->setColumns(4)->onlyWhenUpdating(),
                        AssociationField::new('reserve')
                            ->setLabel('Réservé:')
                            ->setDisabled(true)
                            ->setTextAlign('center')->setColumns(4)->onlyWhenUpdating(),
                        AssociationField::new('offSiteOccasionSale')
                            ->setLabel('Vendu / donner (Hors site):')
                            ->renderAsHtml()
                            ->setDisabled(true)->setTextAlign('center')->setColumns(6)->onlyWhenUpdating(),
                        AssociationField::new('documentLines','Ligne de document:')->setDisabled(true)->setTextAlign('center')->setColumns(12)->onlyWhenUpdating(),


                    FormField::addTab('Création / Mise à jour')->onlyWhenUpdating(),
                        DateTimeField::new('createdAt')->setLabel('Créé le')
                            ->setFormat('dd-MM-yyyy')
                            ->setDisabled()
                            ->setColumns(6)
                            ->onlyWhenUpdating(),
                        AssociationField::new('createdBy')->setLabel('Créé par')
                            ->setFormTypeOption('choice_label', 'nickname')
                            ->setDisabled(true)
                            ->setColumns(6)
                            ->setFormTypeOptions(['placeholder' => 'Créateur de la boite...'])
                            ->onlyWhenUpdating(),        
                
            ];
        }

        if($benevoleView){
            return [

                
                FormField::addTab('Actions / Pramètres'),
                    TextField::new('reference')->setLabel('Référence')->setDisabled(true)->setTextAlign('center')->setColumns(4)->onlyOnIndex(),
                    BooleanField::new('isOnline')->setLabel('En ligne')->setColumns(6)->setDisabled(true)->setDisabled(true),
                    BooleanField::new('isReserved')->setLabel('Réservé')->setColumns(6)->setDisabled(true),
                    BooleanField::new('isBilled')->setLabel('Vendu / donner')->setColumns(6)->setDisabled(true),
                    AssociationField::new('stock','Visible dans le stock:')->setColumns(4)->setDisabled(true)->renderAsEmbeddedForm()->onlyOnDetail(),
                    
                FormField::addTab('Fiche de l\'occasion'),
                    ImageField::new('boite.image')
                        ->setBasePath($this->getParameter('app.path.boites_images'))
                        ->setLabel('Image')
                        ->onlyOnIndex()
                        ->setPermission('ROLE_BENEVOLE'),
                    TextField::new('reference')->setLabel('Référence')->setDisabled(true)->setTextAlign('center')->setColumns(4)->onlyOnDetail(),
                    AssociationField::new('boite')
                        ->setLabel('Dépend de la boite')
                        ->setFormTypeOptions(
                            [
                                'placeholder' => 'Sélectionner...',
                            ]
                            )
                        ->setQueryBuilder(
                            fn(QueryBuilder $queryBuilder) => 
                            $queryBuilder
                            ->where('entity.isOccasion = :value')
                            ->setParameter('value', true)
                            ->orderBy('entity.id', 'ASC')
                        )
                        ->setDisabled(true)
                        ->setColumns(10),
                    BooleanField::new('isNew')
                        ->setLabel('Neuf')
                        ->setDisabled(true)
                        ->setColumns(2),
                    TextField::new('boxEquipnmentAndRulesConditions')
                        ->setLabel('État boite / materiel / Régle du jeu')
                        ->setTextAlign('center'),
                    AssociationField::new('boxCondition')
                        ->setLabel('État de la boite')
                        ->autocomplete()
                        ->onlyOnDetail()
                        ->setDisabled($disabledIfBilled)
                        ->renderAsEmbeddedForm()
                        ->setColumns(4),
                    AssociationField::new('equipmentCondition')
                        ->autocomplete()
                        ->setLabel('État du matériel')
                        ->onlyOnDetail()
                        ->renderAsEmbeddedForm()
                        ->setDisabled($disabledIfBilled)
                        ->setColumns(4),
                    AssociationField::new('gameRule')
                        ->setLabel('Régle du jeu')->autocomplete()
                        ->onlyOnDetail()
                        ->renderAsEmbeddedForm()
                        ->setDisabled($disabledIfBilled)
                        ->setColumns(4),
                    TextField::new('information')
                        ->setLabel('Information sur l\'occasion')
                        ->setDisabled($disabledIfBilled)
                        ->onlyOnDetail()->setColumns(12),

                FormField::addTab('Prix'),
                    BooleanField::new('isNew')
                        ->setLabel('Jeu neuf')
                        ->onlyOnDetail()
                        ->setDisabled($disabledIfBilled)
                        ->setColumns(12),
                    MoneyField::new('boite.htPrice')
                        ->setLabel('Prix HT de référence (boite):')
                        ->setDisabled(true)
                        ->setStoredAsCents()
                        ->setCurrency('EUR')
                        ->onlyOnDetail()->setColumns(4)->setTextAlign('center'),
                    MoneyField::new('virtualPriceWithoutTax','Prix de référence différent de la boite')->onlyOnDetail()->setCurrency('EUR')->setColumns(4)->setTextAlign('center')->setStoredAsCents(),
                    MoneyField::new('priceWithoutTax')
                        ->setLabel('Prix de vente HT:')
                        ->setStoredAsCents()
                        ->setCurrency('EUR')
                        ->onlyOnDetail()
                        ->setDisabled($disabledIfBilled)->setColumns(4)->setTextAlign('center'),
                    // MoneyField::new('discountedPriceWithoutTax')
                    //     ->setLabel('Prix remisé HT:')
                    //     ->setStoredAsCents()
                    //     ->setCurrency('EUR')
                    //     ->onlyWhenUpdating()
                    //     ->setDisabled($disabledIfBilled)->setColumns(4)->setTextAlign('center'),
                    TextField::new('priceWithoutTaxAndDiscountedPriceWithoutTax', 'Prix normal / remisé')->setTextAlign('center'), 
                
            ]; 
        }

 
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des occasions')
            ->setPageTitle('new', 'Nouvel occasion')
            ->setPageTitle('edit', 'Édition d\'un occasion')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['reference', 'boite.name', 'boite.editor.name','id'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {

        $id = $this->requestStack->getCurrentRequest()->get('entityId');
        if($id){
            $occasion = $this->occasionRepository->findOneBy(['id' => $id]);
            $viewOnWebsite = Action::new('viewOnWebsite', 'Voir sur le site', 'fa-solid fa-globe')->linkToRoute('occasion', ['reference_occasion' => $occasion->getReference(), 'boite_slug' => $occasion->getBoite()->getSlug(), 'editor_slug' => $occasion->getBoite()->getEditor()->getSlug()])->setHtmlAttributes(['target' => '_blank'])->setCssClass('btn btn-success');
            return $actions
                ->add(Crud::PAGE_INDEX, $viewOnWebsite)
                ->add(Crud::PAGE_INDEX, Action::DETAIL)
                ->remove(Crud::PAGE_INDEX, Action::NEW)
                ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
                ->setPermission(Action::NEW, 'ROLE_ADMIN')
                ->setPermission(Action::EDIT, 'ROLE_ADMIN');

        }else{

            return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN');
        }
        
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('boite')
            ->add('isOnline','En ligne')
            ->add('priceWithoutTax')
            ->add('paniers','Panier en cours')
            ->add('isReserved','Réservé')
            ->add('isBilled','Vendu')
            ->add('id')
        ;
    }
    
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {

        if($entityInstance instanceof Occasion) {

            $user = $this->security->getUser();


            //?on récupère le prix de la boite
            $boitePriceWithoutTax = $entityInstance->getBoite()->getHtPrice();

            //?on gere en function s'il est neuf ou pas
            if($entityInstance->isIsNew()) {

                $occasionPriceWithoutTaxFromDiscounts = $boitePriceWithoutTax;

            }elseif(!is_null($entityInstance->getVirtualPriceWithoutTax())) { //si on a renseigné un prix virtuel

                $occasionPriceWithoutTaxFromDiscounts = $entityInstance->getVirtualPriceWithoutTax();

            }else{
                //?on recupere la décote en fonction de l'état de la boite
                $discountBoiteState = $entityInstance->getBoxCondition()->getDiscount();
                //?on recupere la décote en fonction de l'état du materiel
                $discountMaterialState = $entityInstance->getEquipmentCondition()->getDiscount();
                //?on recupere la décote en fonction de l'état de la règle du jeu
                $discountRuleState = $entityInstance->getGameRule()->getDiscount();
    
                $occasionPriceWithoutTaxFromDiscounts = $boitePriceWithoutTax - $discountBoiteState - $discountMaterialState - $discountRuleState;
            }

            $entityInstance->setIsOnline(true)->setPriceWithoutTax($occasionPriceWithoutTaxFromDiscounts)->setCreatedAt(new DateTimeImmutable ('now'))->setCreatedBy($user)->setReference('to_create');
            $entityManager->persist($entityInstance);

            $entityManager->flush();

            //?puis on reflush avec la bonne référence
            $entityInstance->setReference($entityInstance->getBoite()->getId().'-'.$entityInstance->getId());
            $entityManager->persist($entityInstance);
            $entityManager->flush();

        }

            
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if($entityInstance instanceof Occasion) {

            if(!$entityInstance->getOffSiteOccasionSale()) {
                
                //?on récupère le prix de la boite
                $boitePriceWithoutTax = $entityInstance->getBoite()->getHtPrice();

                if($entityInstance->getPriceWithoutTax() > $boitePriceWithoutTax) {

                    $occasionPriceWithoutTaxFromDiscounts = $entityInstance->getPriceWithoutTax();
                    
                }else {
    
                    //?on recupere la décote en fonction de l'état de la boite
                    $discountBoiteState = $entityInstance->getBoxCondition()->getDiscount();
                    //?on recupere la décote en fonction de l'état du materiel
                    $discountMaterialState = $entityInstance->getEquipmentCondition()->getDiscount();
                    //?on recupere la décote en fonction de l'état de la règle du jeu
                    $discountRuleState = $entityInstance->getGameRule()->getDiscount();
        
                    $occasionPriceWithoutTaxFromDiscounts = $boitePriceWithoutTax - $discountBoiteState - $discountMaterialState - $discountRuleState;
                }

                $entityInstance->setPriceWithoutTax($occasionPriceWithoutTaxFromDiscounts);
            }

            $entityManager->persist($entityInstance);

            $entityManager->flush();
        }
    }
}
