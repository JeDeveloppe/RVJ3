<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function __construct(
        private Security $security,
        private ParameterBagInterface $parameter_bag_interface,
        private UserPasswordHasherInterface $userPasswordHasher,
        private RequestStack $requestStack
    )
    {
    }

    public function configureFields(string $pageName): iterable
    {
        $hierarchyRoles = array_keys($this->parameter_bag_interface->get('security.role_hierarchy.roles'));
        $userRoles = $this->security->getUser()->getRoles();

        //?On prend le role le plus haut REELLEMENT detenu (index le plus petit dans la
        //?hierarchie), pas le premier du tableau JSON stocke : son ordre est parfois
        //?desordonne (ex: ["ROLE_BENEVOLE", "ROLE_USER", "ROLE_ADMIN"]), ce qui pouvait
        //?proposer une liste de roles plus restreinte que ce a quoi l'admin a vraiment droit.
        $ownRoleIndexes = array_filter(
            array_map(fn($role) => array_search($role, $hierarchyRoles), $userRoles),
            fn($index) => $index !== false
        );
        $roleMaxKey = empty($ownRoleIndexes) ? count($hierarchyRoles) : min($ownRoleIndexes);

        $roles = [];
        foreach ($hierarchyRoles as $key => $role) {
            if($key >= $roleMaxKey){
                
                $roles[$role] = $role;
            }
        }
        $roles['ROLE_USER'] = 'ROLE_USER';


        return [

            FormField::addTab('Infos générales'),
            TextField::new('accountnumber','Numéro client')
                ->setDisabled(true)
                ->setColumns(4)->setTextAlign('center'),
            ArrayField::new('roles', 'Role(s)')
                ->setTemplatePath('admin/fields/roles_list.html.twig')
                ->onlyOnIndex(),
            ChoiceField::new('roles')
                ->setLabel('Role')
                ->setPermission('ROLE_ADMIN')
                ->setColumns(4)
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Choisir un rôle']])->setTextAlign('center')
                ->setChoices($roles)
                ->onlyOnForms()
                ->allowMultipleChoices(true),

            TextField::new('email')
                ->setLabel('Adresse email')
                ->setColumns(6)
                ->setDisabled(true)->setTextAlign('center'),
            TextField::new('plainPassword', 'Nouveau mot de passe')
                ->setFormType(PasswordType::class)
                ->setFormTypeOptions(['required' => false, 'attr' => ['autocomplete' => 'new-password', 'placeholder' => 'Laisser vide pour ne pas changer']])
                ->setHelp('Laisser vide pour ne pas modifier le mot de passe actuel.')
                ->setPermission('ROLE_ADMIN')
                ->setColumns(6)->setTextAlign('center')
                ->onlyOnForms(),
            TextField::new('nickname')
                ->setLabel('Pseudo (pour les admins)')
                ->onlyOnForms()
                ->setColumns(6)
                ->setPermission('ROLE_SUPER_ADMIN')
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
            //?Retire du formulaire d'edition (onlyOnForms) : EasyAdmin reconstruit toute la
            //?config de champs de DocumentCrudController (formulaires imbriques) pour chaque
            //?document du user, ce qui epuise la memoire (meme bug deja rencontre sur
            //?BoiteCrudController::documentLines). Voir l'action voirDocuments() ci-dessous.
            CollectionField::new('documents')->onlyOnDetail(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des inscrits')
            ->setPageTitle('new', 'Nouvel inscrit')
            ->setPageTitle('edit', 'Édition d\' un inscrit')
            ->setSearchFields(['roles', 'email','id','nickname','accountnumber', 'addresses.lastname', 'addresses.firstname', 'addresses.organization']);
    }

    public function configureActions(Actions $actions): Actions
    {
        //?Page dediee hors du systeme de champs/formulaire EasyAdmin (voir voirDocuments()
        //?plus bas), meme principe que BoiteCrudController::voirVentes().
        $voirDocuments = Action::new('voirDocuments', 'Voir les documents')
            ->linkToCrudAction('voirDocuments')
            ->setCssClass('btn btn-secondary');

        return $actions
            ->add(Crud::PAGE_EDIT, $voirDocuments)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN');
    }

    //?Page independante, ne passe pas par configureFields()/le formulaire EasyAdmin : simple
    //?lecture, pas de reconstruction de sous-formulaire par document (cf. bug memoire ci-dessus).
    #[AdminRoute('/{entityId}/documents')]
    public function voirDocuments(EntityManagerInterface $entityManager): Response
    {
        //?Remplace Request::get() (deprecated depuis symfony/http-foundation 7.4) : entityId
        //?vient toujours de la route ici (/{entityId}/documents), donc attributes suffit.
        $userId = $this->requestStack->getCurrentRequest()->attributes->get('entityId');
        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }

        return $this->render('admin/user/documents.html.twig', [
            'user' => $user,
        ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isMemberStructure');
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {

            if ($entityInstance->getPlainPassword()) {
                $entityInstance->setPassword(
                    $this->userPasswordHasher->hashPassword($entityInstance, $entityInstance->getPlainPassword())
                );
                $entityInstance->eraseCredentials();
            }

            $roles = $entityInstance->getRoles();
            if($entityInstance->getEmail() == 'jedeveloppe.contact@gmail.com'){ //protection jedeveloppe
                $roles[] = 'ROLE_SUPER_ADMIN';
            }
            if($entityInstance->getEmail() == 'antoine.gf@hotmail.fr'){ //protection antoine
                $roles[] = 'ROLE_ADMIN';
            }
            $entityInstance->setRoles($roles);

            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder 
    { 
        $user = $this->security->getUser();

        $response = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        if(in_array('ROLE_BENEVOLE',$user->getRoles())){
            //?L'entite Level a ete retiree : on filtre directement sur la colonne JSON
            //?"roles" (LIKE sur sa forme texte, les roles sont toujours entre guillemets
            //?dans le JSON donc pas de faux positif entre ROLE_ADMIN/ROLE_SUPER_ADMIN).
            //?Masque les comptes admin/super-admin au benevole, garde clients + benevoles.
            $response->andWhere('entity.roles NOT LIKE :adminRole')
                ->andWhere('entity.roles NOT LIKE :superAdminRole')
                ->setParameter('adminRole', '%"ROLE_ADMIN"%')
                ->setParameter('superAdminRole', '%"ROLE_SUPER_ADMIN"%');
        }
        return $response;
    }
}
