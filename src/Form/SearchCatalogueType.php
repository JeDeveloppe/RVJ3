<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

//?Formulaire de recherche du catalogue public de pieces detachees, presente en phrase :
//?"Je cherche [un jeu / une piece detachee] qui contient le mot [...]". Remplace l'ancien
//?champ texte unique avec syntaxe espace/+ (SearchBoiteInCatalogueType, toujours utilise par
//?le catalogue "structures adherentes") par un choix explicite et exclusif du perimetre.
class SearchCatalogueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('searchScope', ChoiceType::class, [
                'label' => false,
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'choices' => [
                    'un jeu' => 'jeu',
                    'une pièce détachée' => 'piece',
                ],
                'attr' => [
                    'class' => 'form-select d-inline-block w-auto',
                ],
            ])
            ->add('search', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => 'un mot...',
                    'class' => 'form-control text-dark d-inline-block w-auto',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Ne peut pas être vide...',
                    ),
                    new Length(
                        min: 3,
                        minMessage: 'Minimum {{ limit }} charactères',
                        max: 50,
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
