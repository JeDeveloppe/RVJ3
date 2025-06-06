<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Bundle\SecurityBundle\Security;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function __construct(
        private Security $security
    )
    {
    }

    public function configureFields(string $pageName): iterable
    {

        return [

            FormField::addTab('Infos générales'),
            TextField::new('accountnumber','Numéro client')
                ->setDisabled(true)
                ->setColumns(6)->setTextAlign('center'),
            AssociationField::new('level')
                ->setLabel('Role')
                ->setPermission('ROLE_SUPER_ADMIN')
                ->setColumns(6)
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Choisir un rôle']])->setTextAlign('center'),
            TextField::new('email')
                ->setLabel('Adresse email')
                ->setColumns(6)
                ->setDisabled(true)->setTextAlign('center'),
            TextField::new('password', 'Mot de passe')
                ->setLabel('Mot de passe')
                ->setDisabled(true)->setColumns(6)->setTextAlign('center')
                ->onlyOnForms(),
            TextField::new('nickname')
                ->setLabel('Pseudo (pour les admins)')
                ->onlyOnForms()
                ->setColumns(6)
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Uniquement pour un admin...']])->setTextAlign('center'),
            TelephoneField::new('phone')
                ->setLabel('Téléphone')
                ->setColumns(6)
                ->onlyOnForms()->setTextAlign('center'),
            DateTimeField::new('membership')
                ->setLabel('Abonnement jusqu\'au')
                ->setFormat('dd.MM.yyyy')->onlyOnForms()
                ->setDisabled(true)
                ->setColumns(6)->setTextAlign('center'),
            DateTimeField::new('createdAt')
                ->setLabel('Date d\'inscription')
                ->setFormat('dd.MM.yyyy')
                ->setDisabled(true)
                ->setColumns(4)->setTextAlign('center'),
            DateTimeField::new('lastvisite')
                ->setLabel('Dernière visite')
                ->setFormat('dd.MM.yyyy')
                ->setDisabled(true)
                ->setColumns(4)->setTextAlign('center'),

            FormField::addTab('Adresses'),
            AssociationField::new('addresses')->setLabel('Adresses')->onlyOnIndex()->setColumns(12),
            CollectionField::new('addresses')->setLabel('Adresses')->onlyOnDetail()->setColumns(12),
            ArrayField::new('addresses')->setLabel('Adresses')->onlyOnForms()->setDisabled(true),
            
            FormField::addTab('Documents'),
            AssociationField::new('documents')->onlyOnIndex(),
            CollectionField::new('documents')->onlyOnForms()->setDisabled(true),
            CollectionField::new('documents')->onlyOnDetail(),
            // CollectionField::new('documentLines')->setTemplatePath('admin/fields/documentLines.html.twig')->setDisabled(true)->onlyOnDetail(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des inscrits')
            ->setPageTitle('new', 'Nouvel inscrit')
            ->setPageTitle('edit', 'Édition d\' un inscrit')
            ->setSearchFields(['level.name', 'email','id','nickname','accountnumber']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN');
        
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            
            if(is_null($entityInstance->getLevel())){
                $role = 'ROLE_USER';
                $nickname = 'ROLE_USER #'.$entityInstance->getId();
            }else{
                $role = $entityInstance->getLevel()->getNameInDatabase();
                if(is_null($entityInstance->getLevel()->getName())){
                    $nickname = NULL;
                }else{
                    $nickname = $entityInstance->getNickname();
                }
            }

            $roleMax = [];
            $roleMax[] = $role;
            $entityInstance->setRoles($roleMax)->setNickname($nickname);

            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder 
    { 
        $user = $this->security->getUser();

        $response = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters); 
        if(in_array('ROLE_BENEVOLE',$user->getRoles())){
            $response->join('entity.level', 'l')->where("l.nameInDatabase = 'ROLE_USER'")->where("l.nameInDatabase = 'ROLE_BENEVOLE'"); 
        }
        return $response; 
    }
}
