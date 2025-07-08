<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Boite;
use DateTimeImmutable;
use App\Service\UserService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;

class BoiteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Boite::class;
    }
    public function __construct(
        private Security $security,
        private UserService $userService,
        private AdminUrlGenerator $adminUrlGenerator,
        private RequestStack $requestStack,
        private SluggerInterface $slugger
    ) {}
    public function configureFields(string $pageName): iterable
    {
        //?gestion possibilité d'afficher ou pas en function du role
        $disabledWhenBenevole = $this->userService->disabledFieldWhenBenevole();

        $adminView = $this->isGranted('ROLE_ADMIN');
        $benevoleView = $this->isGranted('ROLE_BENEVOLE');

        if($adminView){

            return [
                FormField::addTab('Fiche de la boite')->setPermission('ROLE_ADMIN'),
                FormField::addFieldset('Actions / Pramètres'),
                BooleanField::new('isOnline')
                    ->setLabel('Actif pièces détachée')
                    ->setPermission('ROLE_ADMIN')
                    ->onlyOnForms()
                    ->setColumns(6),
                BooleanField::new('isOccasion')
                    ->setLabel('Dispo en occasion')
                    ->setColumns(6)
                    ->onlyOnForms()
                    ->setPermission('ROLE_ADMIN'),
                BooleanField::new('isForAdherenteStructure', 'Actif structures adhérentes')->setPermission('ROLE_ADMIN')->onlyOnForms()->setColumns(6),
                BooleanField::new('isDeliverable')->setLabel('Livrable')->onlyOnForms()->setColumns(6)->setPermission('ROLE_ADMIN'),
                BooleanField::new('isDeee')->setLabel('Deee')->onlyOnForms()->setColumns(6)->setPermission('ROLE_ADMIN'),
                FormField::addFieldset('Boite'),
                ImageField::new('image')
                    ->setBasePath($this->getParameter('app.path.boites_images'))
                    ->onlyOnIndex()
                    ->setPermission('ROLE_BENEVOLE'),
                TextField::new('imageFile')
                    ->setFormType(VichImageType::class)
                    ->setFormTypeOptions([
                        'required' => false,
                        'allow_delete' => false,
                        'delete_label' => 'Supprimer du serveur ?',
                        'download_label' => '...',
                        'download_uri' => true,
                        'image_uri' => true,
                        // 'imagine_pattern' => '...',
                        'asset_helper' => true,
                    ])
                    ->setLabel('Image')
                    ->onlyOnForms()
                    ->setPermission('ROLE_ADMIN')
                    ->setColumns(6),
                IdField::new('id', 'Référence')->setDisabled(true),
                TextField::new('name')
                    ->setLabel('Nom')
                    ->setPermission('ROLE_BENEVOLE')
                    ->setColumns(6),
                SlugField::new('slug')
                    ->setTargetFieldName('name')
                    ->setLabel('Slug')
                    ->onlyOnForms()
                    ->setPermission('ROLE_ADMIN')
                    ->setColumns(6),
                IntegerField::new('year')
                    ->setLabel('Année')
                    ->setPermission('ROLE_ADMIN')
                    ->setRequired(true)
                    ->setColumns(6)->setHtmlAttribute('placeholder', 'Mettre 0 pour une année inconnue '),
                AssociationField::new('editor')
                    ->setLabel('Éditeur')
                    ->setQueryBuilder(
                        fn(QueryBuilder $queryBuilder) =>
                        $queryBuilder
                            ->orderBy('entity.name', 'ASC')
                    )
                    ->setFormTypeOptions(['placeholder' => 'Sélectionner un éditeur...'])
                    ->setRequired(true)
                    ->setPermission('ROLE_ADMIN')
                    ->setColumns(6),
                TextareaField::new('content')
                    ->setLabel('Contenu d\'une boite entière')
                    ->onlyOnForms()
                    ->setColumns(6),
                TextField::new('contentMessage')
                    ->setLabel('Message d\'alerte sur le contenu de la boite')
                    ->onlyOnForms()
                    ->setPermission('ROLE_ADMIN')
                    ->setColumns(6),
                IntegerField::new('age')
                    ->setLabel('A partir de (âge)')
                    ->onlyOnForms()
                    ->setPermission('ROLE_ADMIN')
                    ->setColumns(6),
                UrlField::new('linktopresentationvideo')
                    ->setLabel('Lien vers la vidéo de présentation du jeu:')
                    ->onlyOnForms()
                    ->setPermission('ROLE_ADMIN')
                    ->setColumns(6),
                AssociationField::new('playersMin')
                    ->setLabel('A partir de (joueurs)')
                    ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                    ->setQueryBuilder(
                        fn(QueryBuilder $queryBuilder) =>
                        $queryBuilder
                            // ->where('entity.isInOccasionFormSearch = :true')
                            // ->setParameter('true', true)
                            ->orderBy('entity.orderOfAppearance', 'ASC')
                    )
                    // ->onlyOnForms()
                    ->setPermission('ROLE_ADMIN')
                    ->setColumns(6)->onlyOnForms(),
                AssociationField::new('playersMax')
                    ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                    ->setQueryBuilder(
                        fn(QueryBuilder $queryBuilder) =>
                        $queryBuilder
                            // ->where('entity.isInOccasionFormSearch = :true')
                            // ->setParameter('true', true)
                            ->orderBy('entity.orderOfAppearance', 'ASC')
                    )
                    ->setLabel('Jusqu\'à (joueurs)')
                    ->onlyOnForms()
                    ->setRequired(true)
                    ->setPermission('ROLE_ADMIN')
                    ->setColumns(6),
                TextField::new('minAndMaxPlayers', 'Joueurs')->onlyOnIndex()->setTextAlign('center'),
                AssociationField::new('durationGame')
                    ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                    ->setLabel('Durée de la partie')
                    ->setRequired(true)
                    ->onlyOnForms()
                    ->setPermission('ROLE_ADMIN')
                    ->setColumns(6),
    
                FormField::addFieldset('Partie occasion')->setPermission('ROLE_BENEVOLE'),
                IntegerField::new('weigth')->setLabel('Poid (en g)')->onlyOnForms()->setColumns(6)->setRequired(true),
                MoneyField::new('htPrice')
                    ->setLabel('Prix HT d\'une boite complête en bon état')
                    ->setStoredAsCents()
                    ->setCurrency('EUR')
                    ->onlyOnForms()->setColumns(6)->setRequired(true),
                BooleanField::new('isOnline')
                    ->setLabel('Actif pièces det.')
                    ->setPermission('ROLE_ADMIN')
                    ->onlyOnIndex()
                    ->setColumns(6),
                BooleanField::new('isOccasion')
                    ->setLabel('Dispo en occas.')
                    ->setColumns(6)
                    ->onlyOnIndex()
                    ->setPermission('ROLE_ADMIN'),
                BooleanField::new('isForAdherenteStructure', 'Actif structures adhérentes')->setPermission('ROLE_ADMIN')->onlyOnIndex()->setColumns(6),
                // AssociationField::new('itemsSecondaire')->setLabel('Articles:')->setPermission('ROLE_ADMIN')->setDisabled(true)->onlyOnForms(),
                FormField::addTab('Ventes rattachées')->onlyWhenUpdating()->setPermission('ROLE_ADMIN'),
                AssociationField::new('documentLines', 'Nbr de ventes')->onlyOnIndex()->setPermission('ROLE_ADMIN'),
                CollectionField::new('documentLines', 'Les ventes')
                    ->setTemplatePath('admin/fields/documentLines.html.twig')
                    ->setDisabled(true)
                    ->onlyWhenUpdating()
                    ->setPermission('ROLE_ADMIN'),
                FormField::addTab('Création / Mise à jour')->onlyWhenUpdating()->setPermission('ROLE_ADMIN'),
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
                DateTimeField::new('updatedAt')->setLabel('Mise à jour le')
                    ->setFormat('dd-MM-yyyy')
                    ->setDisabled()
                    ->setColumns(6)
                    ->onlyWhenUpdating(),
                AssociationField::new('updatedBy')->setLabel('Mise à jour par')
                    ->setFormTypeOption('choice_label', 'nickname')
                    ->setDisabled(true)
                    ->setColumns(6)
                    ->setFormTypeOptions(['placeholder' => '...'])
                    ->onlyWhenUpdating(),
            ];
        }

        if($benevoleView){
            return [
                FormField::addTab('Fiche de la boite'),
                    ImageField::new('image', 'Image')
                        ->setBasePath($this->getParameter('app.path.boites_images')),
                    TextField::new('imageFile', 'ImageFile')
                        ->setFormType(VichImageType::class)
                        ->setFormTypeOptions([
                            'required' => false,
                            'allow_delete' => false,
                            'delete_label' => 'Supprimer du serveur ?',
                            'download_label' => '...',
                            'download_uri' => true,
                            'image_uri' => true,
                            // 'imagine_pattern' => '...',
                            'asset_helper' => true,
                        ])
                        ->onlyOnForms()
                        ->setColumns(6),
                    IdField::new('id', 'Référence')->setDisabled(true),
                    TextField::new('name')
                        ->setLabel('Nom')
                        ->setColumns(12),
                    SlugField::new('slug')
                        ->setTargetFieldName('name')
                        ->setLabel('Slug')
                        ->onlyOnDetail()
                        ->setColumns(6),
                    IntegerField::new('year')
                        ->setLabel('Année')
                        ->setRequired(true)
                        ->setColumns(6)->setHtmlAttribute('placeholder', 'Mettre 1 pour une année inconnue '),
                    AssociationField::new('editor')
                        ->setLabel('Éditeur')
                        ->setQueryBuilder(
                            fn(QueryBuilder $queryBuilder) =>
                            $queryBuilder
                                ->orderBy('entity.name', 'ASC')
                        )
                        ->setFormTypeOptions(['placeholder' => 'Sélectionner un éditeur...'])
                        ->setRequired(true)
                        ->renderAsEmbeddedForm()
                        ->setColumns(6),
                    TextareaField::new('content')
                        ->setLabel('Contenu d\'une boite entière')
                        ->setColumns(6)
                        ->onlyOnDetail(),
                    TextField::new('contentMessage')
                        ->setLabel('Message d\'alerte sur le contenu de la boite')
                        ->onlyOnDetail()
                        ->setColumns(6),
                    IntegerField::new('age')
                        ->setLabel('A partir de (âge)')
                        ->setColumns(6),
                    UrlField::new('linktopresentationvideo')
                        ->setLabel('Lien vers la vidéo de présentation du jeu:')
                        ->onlyOnDetail()
                        ->setColumns(6),
                    TextField::new('minAndMaxPlayers', 'Joueurs')->onlyOnIndex()->setTextAlign('center'),
                    TextField::new('minAndMaxPlayers', 'Joueurs')->onlyOnDetail()->setTextAlign('center'),
                    AssociationField::new('durationGame')
                        ->setFormTypeOptions(['placeholder' => 'Sélectionner...'])
                        ->setLabel('Durée de la partie')
                        ->setRequired(true)
                        ->renderAsEmbeddedForm()
                        ->setColumns(6),
        
                FormField::addTab('Partie occasion'),
                    IntegerField::new('weigth')->setLabel('Poid (en g)')->onlyOnDetail()->setColumns(6)->setRequired(true),
                    MoneyField::new('htPrice')
                        ->setLabel('Prix HT d\'une boite complête en bon état')
                        ->setStoredAsCents()
                        ->setCurrency('EUR')
                        ->onlyOnDetail()->setColumns(6)->setRequired(true),
                        
                FormField::addTab('Actions / Pramètres'),
                    BooleanField::new('isOnline')
                        ->setLabel('Actif pièces détachée')
                        ->onlyOnDetail()
                        ->setColumns(6),
                    BooleanField::new('isOccasion')
                        ->setLabel('Dispo en occasion')
                        ->setColumns(6)
                        ->onlyOnDetail(),
                    BooleanField::new('isDeliverable')->setLabel('Livrable')->onlyOnDetail()->setColumns(6),
                    BooleanField::new('isDeee')->setLabel('Deee')->onlyOnDetail()->setColumns(6),
            ];
        }
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des boites')
            ->setPageTitle('new', 'Nouvelle boite')
            ->setPageTitle('edit', 'Gestion d\'une boite')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['name', 'editor.name', 'id', 'rvj2id']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $urlForCreateOccasion = $this->adminUrlGenerator
            ->setController(OccasionCrudController::class)
            ->setAction(Action::NEW)
            ->set('boiteShell', $this->requestStack->getCurrentRequest()->get('entityId'))
            ->generateUrl();
        $createOccasion = Action::new('createOccasion', '+ Occasion')
            ->linkToUrl($urlForCreateOccasion)->setCssClass('btn btn-success')
            ->displayIf(fn ($entity) => $entity->isIsOccasion() == true);

        $urlForCreateArticle = $this->adminUrlGenerator
            ->setController(ItemCrudController::class)
            ->setAction(Action::NEW)
            ->set('boiteShell', $this->requestStack->getCurrentRequest()->get('entityId'))
            ->generateUrl();
        $createArticle = Action::new('createArticle', '+ Article')
            ->linkToUrl($urlForCreateArticle)->setCssClass('btn btn-success')
            ->displayIf(fn ($entity) => $entity->getIsOnline() == true);


        return $actions
            ->add(Crud::PAGE_EDIT, $createArticle)
            ->add(Crud::PAGE_EDIT, $createOccasion)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->addBatchAction(Action::new('approve', 'Approve Users')
                ->linkToCrudAction('approveUsers')
                ->addCssClass('btn btn-primary')
                ->setIcon('fa fa-user-check'))
            // ->add(Crud::PAGE_INDEX, $createOccasion)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('age')
            ->add('isOccasion')
            ->add('editor')
            ->add('durationGame')
            ->add('weigth')
        ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addWebpackEncoreEntry('easyAdmin');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Boite) {
            $user = $this->security->getUser();
            $entityInstance->setIsOccasion(true)->setCreatedAt(new DateTimeImmutable('now'))->setCreatedBy($user)->setRvj2id('RVJ3');
            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Boite) {
            $user = $this->security->getUser();
            $entityInstance->setUpdatedBy($user)->setUpdatedAt(new DateTimeImmutable('now'));
            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }
}
