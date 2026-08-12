<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SearchBoiteInCatalogueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => 'Rechercher un jeu, une pièce...',
                    'class' => 'form-control text-dark',
                    //?Bulle d'aide (Bootstrap popover, initialisee dans site.js/structure.js) qui
                    //?s'affiche au clic/focus - le champ n'est pas assez large pour expliquer les
                    //?2 facons de chercher directement dans le placeholder.
                    'data-bs-toggle' => 'popover',
                    'data-bs-trigger' => 'focus',
                    'data-bs-placement' => 'bottom',
                    'data-bs-html' => 'true',
                    'title' => 'Comment chercher ?',
                    'data-bs-content' => '<strong>chat monopoly</strong> : recherche large, n\'importe quel mot n\'importe où.<br><strong>chat + monopoly</strong> : recherche précise, un mot dans une pièce et l\'autre dans le jeu.',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Ne peut pas être vide...',
                    ),
                    new Length(
                        min: 3,
                        minMessage: 'Minimum {{ limit }} charactères',
                        // max length allowed by Symfony for security reasons
                        max: 50,
                    )
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
