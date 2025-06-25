<?php

namespace App\Form;

use App\Entity\DocumentLine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextareaType::class, [
                'label' => 'Question du client sur la pièce:',
                'required' => false,
                'attr' => ['class' => 'form-control', 'readonly' => true],
            ])
            ->add('answer', TextareaType::class, [
                'label' => 'Réponse:',
                'required' => true,
                'attr' => ['rows' => 3, 'class' => 'form-control', 'readonly' => true],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité:',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('priceExcludingTax', MoneyType::class, [
                'label' => 'Prix HT (en €):',
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control'],
                'currency' => false,
                'divisor' => 100,
            ])
            // ->add('deliveryPriceExcludingTax', MoneyType::class, [
            //     'label' => 'Prix de livraison HT:',
            //     'currency' => 'EUR',
            //     'scale' => 2,
            //     'divisor' => 100, // Stocké en centimes dans l'entité
            //     'html5' => true,
            // ])
            // Ajoutez d'autres champs si DocumentLine a des propriétés comme 'weight', 'boite', 'occasion', 'item'
            // et que vous souhaitez les rendre modifiables via ce formulaire
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DocumentLine::class,
        ]);
    }
}