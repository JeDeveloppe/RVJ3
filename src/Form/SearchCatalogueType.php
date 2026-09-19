<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

//?Formulaire de recherche du catalogue public de pieces detachees, presente en phrase :
//?"Je cherche une piece pour le jeu : [...]". Recherche uniquement sur le jeu (nom, editeur,
//?tags), pas de choix de perimetre. L'ancien champ texte a syntaxe espace/+
//?(SearchBoiteInCatalogueType) reste utilise par le catalogue "structures adherentes".
class SearchCatalogueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => 'nom du jeu...',
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
