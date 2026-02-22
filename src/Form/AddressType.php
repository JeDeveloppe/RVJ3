<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\City;
use Symfony\Component\Form\AbstractType;
use App\Form\AdressesVilleAutocompleteField;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $edit = $options['edit'];

        $builder
            ->add('isFacturation', ChoiceType::class, [
                'label' => 'Adresse de:',
                'placeholder' => 'Faire un choix...',
                'disabled' => $edit,
                'attr' => [
                    'class' => 'form-control',
                ],
                'choices' => [
                    'FACTURATION' => true,
                    'LIVRAISON' => false
                    ]
                ])
            ->add('organization', TextType::class, [
                'label' => 'Organisation:',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom:',
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom:',
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('street', TextType::class, [
                'label' => 'Adresse complète:',
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('city', AdressesVilleAutocompleteField::class, [
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
            'edit' => null ?? false,
        ]);
    }
}
