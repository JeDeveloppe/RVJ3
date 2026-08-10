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
                    'placeholder' => 'Rechercher un jeu, un éditeur...',
                    'class' => 'form-control text-dark'
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
