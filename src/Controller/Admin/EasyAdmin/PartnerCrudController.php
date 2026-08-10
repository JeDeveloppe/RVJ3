<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Partner;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PartnerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Partner::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ImageField::new('image')->setBasePath($this->getParameter('app.path.partners_images'))->onlyOnIndex(),
            TextField::new('imageFile')->setFormType(VichImageType::class)->setLabel('Image')->onlyOnForms(),
            TextField::new('name')->setLabel('Nom'),
            TextField::new('fullUrl')->setLabel('Adresse web')->onlyOnForms(),
            TextareaField::new('description')->setLabel('Description')->onlyOnForms(),
            TextareaField::new('collect')->setLabel('Collecte')->onlyOnForms(),
            TextareaField::new('sells')->setLabel('Vend')->onlyOnForms(),
            //?autocomplete() obligatoire : la table city fait 38 500+ lignes, sans ca EasyAdmin
            //?les rend toutes en options inline dans le formulaire.
            AssociationField::new('city')->setLabel('Ville')->autocomplete()->onlyOnForms(),
            //?renderAsEmbeddedForm() retire : reconstruit toute la config de champs de
            //?CityCrudController pour chaque ligne de la liste des partenaires (meme cause que
            //?les autres plantages memoire deja corriges) - un simple libelle suffit ici,
            //?City::__toString() est bon marche (pas de relation chargee paresseusement).
            AssociationField::new('city')->setLabel('Ville')->onlyOnIndex(),
            BooleanField::new('isAcceptDonations')->setLabel('Accepte les dons')->onlyOnForms(),
            BooleanField::new('isSellsSpareParts')->setLabel('Vend des pièces détachées')->onlyOnForms(),
            BooleanField::new('isSellFullGames')->setLabel('Vend des jeux complets')->onlyOnForms(),
            BooleanField::new('isDisplayOnCatalogueWhenSearchIsNull')->setLabel('Affichage catalogue si recherche NULL'),
            BooleanField::new('isWebShop')->setLabel('Eboutique')->onlyOnForms(),
            BooleanField::new('isOnline')->setLabel('En ligne'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des partenaires')
            ->setPageTitle('new', 'Nouveau partenaire')
            ->setPageTitle('edit', 'Édition du partenaire')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN');
        
    }

}
