<?php

namespace App\Form;

use App\Entity\Boite;
use App\Entity\QuoteRequest;
use App\Entity\QuoteRequestLine;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteRequestLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('boite', EntityType::class, [ // Supposons que votre QuoteRequestLine a une propriété 'boite'
                'class' => Boite::class,
                'choice_label' => 'getNameAndReference', // Ou 'title', ou la propriété que vous voulez afficher pour choisir la boite
                'placeholder' => 'Sélectionner une boîte',
                'required' => false, // Selon si le lien est obligatoire ou non
                'label' => 'Boîte associée', // Label pour le select
                'attr' => [
                    'class' => 'form-control',
                ],
                'disabled' => true,
            ])
            ->add('question', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'disabled' => true
            ])
            ->add('answer', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control',
                ]
            ])   
            ->add('priceExcludingTax', MoneyType::class, [
                'label' => 'Prix total HT (en €):',
                'divisor' => 100,
                'attr' => [
                    'class' => 'form-control',
                ]
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteRequestLine::class,
        ]);
    }
}
