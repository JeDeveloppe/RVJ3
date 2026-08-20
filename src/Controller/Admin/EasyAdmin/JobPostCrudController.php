<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\JobPost;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JobPostCrudController extends AbstractCrudController
{
    private const CONTRACT_TYPES = ['CDI', 'CDD', 'Bénévole', 'Stage', 'Alternance'];

    public static function getEntityFqcn(): string
    {
        return JobPost::class;
    }

    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Offre d\'emploi');
        yield BooleanField::new('isOnLine')->setLabel('Visible sur le site:')->setColumns(3);
        yield DateField::new('startPublished')->setLabel('Début de publication:')->setRequired(true)->setColumns(4)->onlyOnForms();
        yield DateField::new('endPublished')->setLabel('Fin de publication:')->setRequired(true)->setColumns(4)->onlyOnForms();
        yield DateField::new('startPublished')->setLabel('Début:')->onlyOnIndex();
        yield DateField::new('endPublished')->setLabel('Fin:')->onlyOnIndex();
        yield TextField::new('status')->setLabel('Statut')->setVirtual(true)->onlyOnIndex()
            ->formatValue(function ($value, JobPost $jobPost) {
                if (!$jobPost->isIsOnLine()) {
                    return '⚪ Hors ligne';
                }
                if ($jobPost->isExpired()) {
                    return '🔴 Expirée';
                }
                if ($jobPost->getStartPublished() > new DateTimeImmutable('now')) {
                    return '🔵 Programmée';
                }

                return '🟢 En ligne';
            });
        yield TextField::new('title')->setLabel('Titre:')->setRequired(true)->setColumns(12);
        yield SlugField::new('slug')->setLabel('URL (générée depuis le titre) :')->setTargetFieldName('title')->hideOnIndex();
        yield ChoiceField::new('contractType')->setLabel('Type de contrat:')->setChoices(array_combine(self::CONTRACT_TYPES, self::CONTRACT_TYPES))->setRequired(true)->setColumns(4);
        yield TextField::new('location')->setLabel('Lieu:')->setRequired(false)->setColumns(8);
        yield TextareaField::new('description')->setLabel('Description de l\'offre:')->setColumns(12)->onlyOnForms()
            ->setFormTypeOption('attr', ['data-controller' => 'rich-text-editor', 'rows' => 10])
            ->addJsFiles(Asset::new('build/tinymce/tinymce.min.js')->onlyOnForms())
            ->setHelp('Barre d\'outils au-dessus du texte : titres, gras, italique, souligné, barré, listes, lien, tableau.');

        yield FormField::addTab('Création / Mise à jour')->onlyWhenUpdating();
        yield AssociationField::new('createdBy')->setLabel('Créé par')->setDisabled(true)->onlyWhenUpdating();
        yield DateTimeField::new('createdAt')->setLabel('Créé le')->setDisabled(true)->onlyWhenUpdating();
        yield AssociationField::new('updatedBy')->setLabel('Mise à jour par')->setDisabled(true)->onlyWhenUpdating();
        yield DateTimeField::new('updatedAt')->setLabel('Mise à jour le')->setDisabled(true)->onlyWhenUpdating();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPageTitle('index', 'Liste des offres d\'emploi')
            ->setPageTitle('new', 'Nouvelle offre d\'emploi')
            ->setPageTitle('edit', 'Édition d\'une offre d\'emploi')
            ->setDefaultSort(['startPublished' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $preview = Action::new('preview', 'Aperçu', 'fa-solid fa-eye')
            ->linkToUrl(fn (JobPost $jobPost) => $this->urlGenerator->generate('admin_job_post_preview', ['id' => $jobPost->getId()]))
            ->setHtmlAttributes(['target' => '_blank'])
            ->setCssClass('btn btn-secondary');

        $viewOnWebsite = Action::new('viewOnWebsite', 'Voir sur le site', 'fa-solid fa-globe')
            ->linkToUrl(fn (JobPost $jobPost) => $this->urlGenerator->generate('app_job_post_show', ['id' => $jobPost->getId(), 'slug' => $jobPost->getSlug()]))
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayIf(fn (JobPost $jobPost) => $jobPost->isIsOnLine())
            ->setCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, $preview)
            ->add(Crud::PAGE_EDIT, $preview)
            ->add(Crud::PAGE_DETAIL, $preview)
            ->add(Crud::PAGE_INDEX, $viewOnWebsite)
            ->add(Crud::PAGE_EDIT, $viewOnWebsite)
            ->add(Crud::PAGE_DETAIL, $viewOnWebsite)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof JobPost) {
            $user = $this->security->getUser();
            $now = new DateTimeImmutable('now');
            $this->normalizePublicationDates($entityInstance);
            $entityInstance->setCreatedAt($now)->setCreatedBy($user)->setUpdatedAt($now)->setUpdatedBy($user);

            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof JobPost) {
            $user = $this->security->getUser();
            $this->normalizePublicationDates($entityInstance);
            $entityInstance->setUpdatedAt(new DateTimeImmutable('now'))->setUpdatedBy($user);

            $entityManager->persist($entityInstance);
            $entityManager->flush();
        }
    }

    private function normalizePublicationDates(JobPost $jobPost): void
    {
        $jobPost->setStartPublished($jobPost->getStartPublished()->setTime(0, 0, 0));
        $jobPost->setEndPublished($jobPost->getEndPublished()->setTime(23, 59, 59));
    }
}
