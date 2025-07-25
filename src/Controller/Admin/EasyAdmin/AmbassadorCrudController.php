<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Ambassador;
use App\Entity\City;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class AmbassadorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Ambassador::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('Informations privées'),
            TextField::new('privatelastname')->setLabel('Nom:')->setColumns(6),
            TextField::new('privatefirstname')->setLabel('Prénom:')->setColumns(6),
            TextField::new('organization')->setLabel('Organisation:')->onlyOnIndex(),
            TelephoneField::new('privatephone')->setLabel('Téléphone:')->onlyOnForms()->setColumns(6),
            EmailField::new('privateemail')->setLabel('Adresse email:')->onlyOnForms()->setColumns(6),
            TextField::new('privatestreet')->setLabel('Adresse complète:'),
            AssociationField::new('privatecity')->setLabel('Ville:')->autocomplete(),
            IntegerField::new('colisSend', 'Nbre de colis<br/> envoyés'),
            
            FormField::addTab('Informations sur la carte'),
            BooleanField::new('onTheCarte','Sur la carte')->setDisabled(true)->onlyOnIndex(),
            BooleanField::new('onTheCarte','Sur la carte')->onlyOnForms(),
            TextField::new('organization')->setLabel('Organisation:')->onlyOnForms(),
            TextField::new('lastname')->setLabel('Nom:')->onlyOnForms()->setColumns(6),
            TextField::new('firstname')->setLabel('Prénom:')->onlyOnForms()->setColumns(6),
            TelephoneField::new('phone')->setLabel('Téléphone:')->onlyOnForms()->setColumns(6),
            EmailField::new('email')->setLabel('Adresse email:')->onlyOnForms()->setColumns(6),
            TextField::new('street')->setLabel('Adresse complète:')->onlyOnForms()->setColumns(6),
            AssociationField::new('city')->setLabel('Ville:')->autocomplete()->onlyOnForms()->setColumns(6)->setRequired(false),
            TextField::new('latitude')->setLabel('Latitude du lieu:')->onlyOnForms()->setColumns(6),
            TextField::new('longitude')->setLabel('Longitude du lieu:')->onlyOnForms()->setColumns(6),
            TextareaField::new('description')->setLabel('Description:')->onlyOnForms()->setColumns(6),
            UrlField::new('fullurl')->setLabel('Adresse url complète:')->onlyOnForms(),
            UrlField::new('facebookLink')->setLabel('Url Facebook:')->onlyOnForms(),
            UrlField::new('instagramLink')->setLabel('Url Instagram:')->onlyOnForms(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des ambassadeurs')
            ->setPageTitle('new', 'Nouvel ambassadeur')
            ->setPageTitle('edit', 'Édition d\'un ambassadeur')
            ->setDefaultSort(['privatelastname' => 'ASC'])
        ;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Ambassador) {

            if($entityInstance->getCity() instanceof City){
                $entityInstance->setOnTheCarte(true);
            }else{
                $entityInstance->setOnTheCarte(false);
            }

            $entityManager->persist($entityInstance);
            $entityManager->flush();
            
        }
    }
}
