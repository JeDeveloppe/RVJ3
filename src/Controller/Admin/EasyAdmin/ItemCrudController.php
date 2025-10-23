<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Item;
use App\Entity\Boite;
use DateTimeImmutable;
use App\Service\ItemService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\HttpFoundation\RequestStack;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ItemCrudController extends AbstractCrudController
{
    public function __construct(
        private Security $security,
        private ItemService $itemService,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager // Injecte l'EntityManager ici
    )
    {
    }
    
    public static function getEntityFqcn(): string
    {
        return Item::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Général');
            yield FormField::addFieldset('Informations');
                yield IdField::new('id')->setLabel('Id')->setColumns(6)->hideOnForm(); // Cache l'ID sur le formulaire de création
                yield TextField::new('reference')->setLabel('Référence')->setColumns(6)->hideOnForm();

            yield FormField::addFieldset('Catalogue');
                yield AssociationField::new('itemGroup')
                    ->setLabel('Groupe d\'articles')
                    ->setRequired(true)
                    ->setFormTypeOptions(['placeholder' => 'Faire un choix...'])
                    ->setColumns(6);
                
                // Champs BoiteOrigine et BoiteSecondaire sont gérés plus simplement
                yield AssociationField::new('BoiteOrigine')
                    ->setLabel('Boite Originale (doit être en ligne)')
                    ->setRequired(true)
                    ->setColumns(6)
                    ->setQueryBuilder(
                        fn(QueryBuilder $queryBuilder) => 
                        $queryBuilder->where('entity.isOnline = :true')->setParameter('true', true)
                    );
                
                yield AssociationField::new('BoiteSecondaire')
                    ->setLabel('Boite Secondaire')
                    ->setColumns(6)
                    ->setFormTypeOptions(['placeholder' => 'Sélectionner une boite...'])
                    ->setDisabled(true); // Champ désactivé car géré automatiquement

            yield FormField::addFieldset('Détails');
                yield ImageField::new('image')
                    ->setBasePath($this->getParameter('app.path.item_images'))
                    ->onlyOnIndex();

                yield TextField::new('imageFile')
                    ->setFormType(VichImageType::class)
                    ->setFormTypeOptions([
                        'required' => $pageName === Crud::PAGE_NEW, // Requis seulement à la création
                        'allow_delete' => false,
                        'download_uri' => true,
                        'image_uri' => true,
                        'asset_helper' => true,
                    ])
                    ->setLabel('Image')
                    ->setColumns(6);

                yield TextareaField::new('comment')
                    ->setLabel('Commentaire')
                    ->setFormTypeOptions(['attr' => ['rows' => 5]])
                    ->setColumns(6);

                yield TextField::new('name')
                    ->setLabel('Nom')
                    ->setColumns(6);

                yield IntegerField::new('stockForSale')
                    ->setLabel('Stock à la vente')
                    ->setColumns(6);

                yield MoneyField::new('priceExcludingTax')
                    ->setLabel('Prix unitaire HT')
                    ->setCurrency('EUR')
                    ->setStoredAsCents()
                    ->setColumns(6);

                yield IntegerField::new('weigth')
                    ->setLabel('Poid (en gramme)')
                    ->setColumns(6);

                yield AssociationField::new('Envelope')
                    ->setLabel('Enveloppe')
                    ->setFormTypeOptions(['placeholder' => 'Sélectionner une enveloppe...'])
                    ->setColumns(6);

        yield FormField::addTab('Ventes')->onlyWhenUpdating();
            yield AssociationField::new('documentLines')
                ->setLabel('Ventes')
                ->onlyOnIndex();

        yield FormField::addTab('Création / Mise à jour')->onlyWhenUpdating();
            yield AssociationField::new('createdBy')->setLabel('Créé par')->setDisabled(true);
            yield DateTimeField::new('createdAt')->setLabel('Créé le')->setDisabled(true);
            yield AssociationField::new('updatedBy')->setLabel('Mise à jour par')->setDisabled(true);
            yield DateTimeField::new('updatedAt')->setLabel('Mise à jour le')->setDisabled(true);
    }

    public function createEntity(string $entityFqcn)
    {
        $item = new Item();
        $user = $this->security->getUser();
        $now = new DateTimeImmutable('now');
        
        // Initialise l'entité avec les valeurs par défaut
        $item->setCreatedAt($now)
             ->setCreatedBy($user)
             ->setUpdatedAt($now)
             ->setUpdatedBy($user);

        // Récupère l'ID de la boite si un paramètre 'boiteShell' est présent dans l'URL
        $boiteShellId = $this->requestStack->getCurrentRequest()->get('boiteShell');
        if ($boiteShellId) {
            $boite = $this->entityManager->getRepository(Boite::class)->find($boiteShellId);
            if ($boite) {
                $item->addBoiteOrigine($boite);
            }
        }

        return $item;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Item) {
            return;
        }

        // D'abord, persiste et flush pour obtenir l'ID
        $entityManager->persist($entityInstance);
        $entityManager->flush();

        // Utilise l'ID pour générer la référence de manière unique
        $boiteOrigine = $entityInstance->getBoiteOrigine()->first();
        if ($boiteOrigine) {
            $reference = $this->itemService->creationReference($boiteOrigine, $entityInstance);
            $entityInstance->setReference($reference);
            
            // Met à jour l'entité avec la référence et flush une dernière fois
            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Item) {
            $user = $this->security->getUser();
            $now = new DateTimeImmutable('now');
            $entityInstance->setUpdatedAt($now)->setUpdatedBy($user);

            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des articles')
            ->setPageTitle('new', 'Nouvel article')
            ->setPageTitle('edit', 'Édition d\'un article')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['name', 'BoiteOrigine.name', 'BoiteSecondaire.id', 'BoiteSecondaire.name', 'reference', 'BoiteOrigine.id'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->remove(Crud::PAGE_INDEX, Action::DELETE);
    }
}