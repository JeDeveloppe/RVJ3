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
        $isGrantedAdmin = $this->isGranted('ROLE_ADMIN');

        // Champs de base, communs à tous les rôles sur la page INDEX
        if (Crud::PAGE_INDEX === $pageName) {
            $fields = [
                IdField::new('id', 'ID'),
                ImageField::new('image', 'Image')
                    ->setBasePath($this->getParameter('app.path.boites_images')),
                TextField::new('name', 'Nom'),
                AssociationField::new('editor', 'Éditeur'),
                TextField::new('minAndMaxPlayers', 'Joueurs')->setTextAlign('center'),
                BooleanField::new('isOccasion', 'Occasion')
                    ->setPermission('ROLE_ADMIN'),
                BooleanField::new('isOnline', 'En Ligne')
                    ->setPermission('ROLE_ADMIN'),
            ];
            // Les champs spécifiques aux admins sur la page INDEX
            if ($isGrantedAdmin) {
                $fields[] = IntegerField::new('weigth', 'Poids')->setPermission('ROLE_ADMIN');
                $fields[] = MoneyField::new('htPrice', 'Prix HT')
                    ->setStoredAsCents()
                    ->setCurrency('EUR')
                    ->setPermission('ROLE_ADMIN');
                $fields[] = BooleanField::new('isForAdherenteStructure', 'Pour Adhérents')->setPermission('ROLE_ADMIN');
            }
            return $fields;
        }

        // Champs pour les pages de CRÉATION et MODIFICATION
        return [
            FormField::addTab('Informations générales'),
            ImageField::new('image', 'Image')
                ->setBasePath($this->getParameter('app.path.boites_images'))
                ->onlyOnIndex(),
            TextField::new('imageFile', 'Image')
                ->setFormType(VichImageType::class)
                ->setFormTypeOptions([
                    'required' => false,
                    'allow_delete' => false,
                ])
                ->onlyOnForms(),
            FormField::addFieldset('Détails de la boîte'),
            IdField::new('id', 'ID de référence')->setDisabled(true),
            TextField::new('name', 'Nom'),
            IntegerField::new('year', 'Année')
                ->setHelp('Mettre 0 pour une année inconnue'),
            AssociationField::new('editor', 'Éditeur')
                ->setQueryBuilder(fn(QueryBuilder $qb) => $qb->orderBy('entity.name', 'ASC'))
                ->setFormTypeOptions(['placeholder' => 'Sélectionner un éditeur...']),
            
            FormField::addFieldset('Partie occasion & Pièces détachées')->setPermission('ROLE_ADMIN'),
            MoneyField::new('htPrice', 'Prix HT')
                ->setStoredAsCents()
                ->setCurrency('EUR')
                ->setRequired(true)
                ->setPermission('ROLE_ADMIN'),
            IntegerField::new('weigth', 'Poids (en g)')
                ->setRequired(true)
                ->setPermission('ROLE_ADMIN'),
            BooleanField::new('isOnline', 'Actif pièces détachées')
                ->setPermission('ROLE_ADMIN'),
            BooleanField::new('isOccasion', 'Disponible en occasion')
                ->setPermission('ROLE_ADMIN'),
            BooleanField::new('isForAdherenteStructure', 'Actif structures adhérentes')
                ->setPermission('ROLE_ADMIN'),
            BooleanField::new('isDeliverable', 'Livrable')
                ->setPermission('ROLE_ADMIN'),
            BooleanField::new('isDeee', 'Deee')
                ->setPermission('ROLE_ADMIN'),
            AssociationField::new('documentLines', 'Nombre de ventes')->onlyOnIndex(),

            FormField::addTab('Détails avancés')->setPermission('ROLE_ADMIN'),
            SlugField::new('slug')->setTargetFieldName('name')
                ->setPermission('ROLE_ADMIN'),
            TextareaField::new('content', 'Contenu d\'une boîte entière')
                ->setPermission('ROLE_ADMIN'),
            TextField::new('contentMessage', 'Message d\'alerte sur le contenu')
                ->setPermission('ROLE_ADMIN'),
            IntegerField::new('age', 'À partir de (âge)')
                ->setPermission('ROLE_ADMIN'),
            AssociationField::new('playersMin', 'Joueurs min')
                ->setPermission('ROLE_ADMIN'),
            AssociationField::new('playersMax', 'Joueurs max')
                ->setRequired(true)
                ->setPermission('ROLE_ADMIN'),
            AssociationField::new('durationGame', 'Durée de la partie')
                ->setRequired(true)
                ->setPermission('ROLE_ADMIN'),
            UrlField::new('linktopresentationvideo', 'Lien vidéo')
                ->setPermission('ROLE_ADMIN'),
            
            FormField::addTab('Ventes rattachées')->onlyWhenUpdating()->setPermission('ROLE_ADMIN'),
            CollectionField::new('documentLines', 'Les ventes')
                ->setTemplatePath('admin/fields/documentLines.html.twig')
                ->setDisabled(true)
                ->onlyWhenUpdating()
                ->setPermission('ROLE_ADMIN'),

            FormField::addTab('Historique')->onlyWhenUpdating()->setPermission('ROLE_ADMIN'),
            DateTimeField::new('createdAt', 'Créé le')->setDisabled()->setColumns(6)->setPermission('ROLE_ADMIN'),
            AssociationField::new('createdBy', 'Créé par')->setDisabled(true)->setColumns(6)->setPermission('ROLE_ADMIN'),
            DateTimeField::new('updatedAt', 'Mis à jour le')->setDisabled()->setColumns(6)->setPermission('ROLE_ADMIN'),
            AssociationField::new('updatedBy', 'Mis à jour par')->setDisabled(true)->setColumns(6)->setPermission('ROLE_ADMIN'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Catalogue des boîtes')
            ->setPageTitle('new', 'Ajouter une boîte')
            ->setPageTitle('edit', 'Modifier une boîte')
            ->setPageTitle('detail', 'Détails de la boîte')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['name', 'editor.name', 'id', 'rvj2id']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $createOccasion = Action::new('createOccasion', '+ Occasion')
            ->linkToCrudAction('createOccasion')
            ->setCssClass('btn btn-success')
            ->displayIf(fn ($entity) => $entity->isIsOccasion());

        $createArticle = Action::new('createArticle', '+ Article')
            ->linkToCrudAction('createArticle')
            ->setCssClass('btn btn-success')
            ->displayIf(fn ($entity) => $entity->getIsOnline());

        return $actions
            ->add(Crud::PAGE_EDIT, $createArticle)
            ->add(Crud::PAGE_EDIT, $createOccasion)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN');
    }

    public function createOccasion(AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager): RedirectResponse
    {
        $boiteId = $this->requestStack->getCurrentRequest()->get('entityId');
        return $this->redirect($adminUrlGenerator->setController(OccasionCrudController::class)
            ->setAction(Action::NEW)
            ->set('boiteShell', $boiteId)
            ->generateUrl());
    }

    public function createArticle(AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager): RedirectResponse
    {
        $boiteId = $this->requestStack->getCurrentRequest()->get('entityId');
        return $this->redirect($adminUrlGenerator->setController(ItemCrudController::class)
            ->setAction(Action::NEW)
            ->set('boiteShell', $boiteId)
            ->generateUrl());
    }
    
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('age')
            ->add('isOccasion')
            ->add('editor')
            ->add('durationGame')
            ->add('weigth');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Boite) {
            $user = $this->security->getUser();
            $entityInstance->setCreatedAt(new DateTimeImmutable('now'))->setCreatedBy($user)->setRvj2id('RVJ3');
        }
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Boite) {
            $user = $this->security->getUser();
            $entityInstance->setUpdatedBy($user)->setUpdatedAt(new DateTimeImmutable('now'));
        }
        parent::updateEntity($entityManager, $entityInstance);
    }
}