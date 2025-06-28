<?php

namespace App\Form;

use App\Entity\ShippingMethod;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteRequestChoiceShippingMethodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingMethod', EntityType::class, [
                'label' => false,
                'class' => ShippingMethod::class,
                'placeholder' => 'Choisir une methode de livraison...',
                'required' => true,
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'form-control',
                ],
                'multiple' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC')
                        ->where('s.isActivedInQuoteRequest = :value')
                        ->setParameter('value', true);
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
