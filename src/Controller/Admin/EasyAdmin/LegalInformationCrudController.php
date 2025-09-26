<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\LegalInformation;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Bundle\SecurityBundle\Security;

class LegalInformationCrudController extends AbstractCrudController
{
    public function __construct(
        private Security $security
    )
    {
    }
    
    public static function getEntityFqcn(): string
    {
        return LegalInformation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('companyName')->setLabel('Nom de la société'),
            TextField::new('publicationManagerFirstName')->onlyOnForms()->setLabel('Responsable de la publication (prénom)'),
            TextField::new('publicationManagerLastName')->onlyOnForms()->setLabel('Responsable de la publication (nom)'),
            TextField::new('streetCompany')->setLabel('Adresse du siège de la société'),
            IntegerField::new('postalCodeCompany')->setLabel('Code postal du siège de la société'),
            TextField::new('cityCompany')->setLabel('Ville du siège de la société'),
            TextField::new('siretCompany')->setLabel('SIRET')->onlyOnForms(),
            EmailField::new('emailCompany')->onlyOnForms()->setLabel('Email de la société'),
            UrlField::new('fullUrlCompany')->onlyOnForms()->setLabel('Url du site de la société'),
            AssociationField::new('countryCompany')->setLabel('Pays de la société')->setFormTypeOptions(['placeholder' => 'Sélectionner un pays...']),
            AssociationField::new('tax')->setLabel('Taxe sur le site')->setFormTypeOptions(['placeholder' => 'Sélectionner une valeur...'])->onlyOnForms(),
            TextField::new('webmasterCompanyName')->onlyOnForms()->setLabel('Socièté du webmaster'),
            TextField::new('webmasterFistName')->onlyOnForms()->setLabel('Prénom du webmaster'),
            TextField::new('webmasterLastName')->onlyOnForms()->setLabel('Nom du webmaster'),
            TextField::new('hostName')->onlyOnForms()->setLabel('Nom de l\'hébergeur'),
            TextField::new('hostStreet')->onlyOnForms()->setLabel('Adresse de l\'hébergeur'),
            IntegerField::new('hostPostalCode')->onlyOnForms()->setLabel('Code postal de l\'hébergeur'),
            TextField::new('hostCity')->onlyOnForms()->setLabel('Ville de l\'hébergeur'),
            TextField::new('hostPhone')->onlyOnForms()->setLabel('Téléphone de l\'hébergeur'),
            AssociationField::new('updatedBy')->setLabel('Dernière modification par')->onlyOnForms()->setDisabled(true),
            DateTimeField::new('updatedAt')->setLabel('Dernière modification enregistrée le')->onlyOnForms()->setDisabled(true),
            BooleanField::new('isOnline')->setLabel('En utilisation')
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des infos légales')
            ->setPageTitle('new', 'Nouvelles infos légales')
            ->setPageTitle('edit', 'Édition infos légales')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN');
        
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if($entityInstance instanceof LegalInformation) {
            $user = $this->security->getUser();
            $entityInstance->setUpdatedBy($user)->setUpdatedAt(new DateTimeImmutable('now'));

            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if($entityInstance instanceof LegalInformation) {
            $user = $this->security->getUser();
            $entityInstance->setUpdatedBy($user)->setUpdatedAt(new DateTimeImmutable('now'));

            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }
}
