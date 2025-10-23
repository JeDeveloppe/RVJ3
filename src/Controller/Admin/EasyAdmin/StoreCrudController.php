<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Store;
use App\Form\StorePhotoFormType;
use App\Form\OpeningHoursFormType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class StoreCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Store::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Nos boutiques')
            ->setPageTitle('new', 'Ajouter une boutique')
            ->setPageTitle('edit', 'Modifier la boutique');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name')
            ->setLabel('Nom du magasin')
            ->setHelp('Nom commercial de la boutique.');
        
        yield AssociationField::new('city')
            ->setLabel('Ville')
            ->autocomplete();

        yield TextField::new('street')
            ->setLabel('Adresse')
            ->setHelp('Numéro et nom de rue.');

        yield TextField::new('googleMapUrl')
            ->setLabel('URL Google Maps')
            ->hideOnIndex()
            ->setHelp('Lien vers la carte Google Maps pour l\'emplacement exact.');

        yield CollectionField::new('openingHours')
            ->hideOnIndex()
            ->setLabel('Horaires d\'ouverture')
            ->setEntryType(OpeningHoursFormType::class)
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->allowAdd(true)
            ->allowDelete(true)
            ->setHelp('Ajoutez et modifiez les horaires de la semaine.');

        // On gère l'affichage des photos selon la page
        if ($pageName !== Crud::PAGE_INDEX) {
            yield CollectionField::new('storePhotos')
                ->setLabel('Photos de la boutique')
                ->setEntryType(StorePhotoFormType::class)
                // Use this path for the custom form template
                ->setTemplatePath('bundles/EasyAdminBundle/crud/form/store_photos_collection.html.twig')
                ->setFormTypeOptions([
                    'by_reference' => false,
                ])
                ->setHelp('Ajoutez et supprimez des photos de la boutique.');
        }
    }
}