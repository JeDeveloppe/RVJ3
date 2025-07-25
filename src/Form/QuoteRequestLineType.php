<?php

namespace App\Form;

use App\Entity\QuoteRequestLine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class QuoteRequestLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
            // ->add('weight', NumberType::class, [
            //     'label' => 'Poids total en gramme:',
            //     'attr' => [
            //         'class' => 'form-control col-11',
            //         'placeholder' => 'Saisir un poids en gramme...'
            //     ]   
            // ])
            ->add('priceExcludingTax', MoneyType::class, [
                'label' => 'Prix total HT (en €):',
                'divisor' => 100,
                'currency' => false,
                'attr' => [
                    'class' => 'form-control col-11',
                    'placeholder' => 'Saisir un prix HT...'
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
