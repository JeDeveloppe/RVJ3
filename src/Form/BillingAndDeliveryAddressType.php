<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\CollectionPoint;
use App\Repository\ShippingMethodRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BillingAndDeliveryAddressType extends AbstractType
{

    public function __construct(
        private ShippingMethodRepository $shippingMethodRepository
        )
    {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        $is_france = $options['is_france'];
        $shippingMethod = $this->shippingMethodRepository->findOneById($options['shippingMethodId']);

        $builder
            ->add('billingAddress', EntityType::class, [
                'class' => Address::class,
                'label' => false,
                'attr' => [
                    'class' => 'col-12',
                ],
                'placeholder' => '-- Adresse de facturation --',
                'multiple' => false,
                'expanded' => true,
                'mapped' => false,
                'query_builder' => function (EntityRepository $er) use ($user) {
                    return $er->createQueryBuilder('a')
                        ->where('a.isFacturation = :value')
                        ->andWhere('a.user = :user')
                        ->setParameter('user', $user)
                        ->setParameter('value', true)
                        ->orderBy('a.id', 'ASC');
                },
            ]);
        
            if($shippingMethod->getPrice() == 'GRATUIT' && $is_france == true){

                $builder
                    ->add('deliveryAddress', EntityType::class, [
                        'class' => CollectionPoint::class,
                        'label' => false,
                        'attr' => [
                            'class' => 'form-control text-center col-12',
                        ],
                        'placeholder' => '-- Adresse de retrait --',
                        'multiple' => false,
                        'expanded' => true,
                        'mapped' => false,
                        'query_builder' => function (EntityRepository $er) use ($shippingMethod) {
                            return $er->createQueryBuilder('c')
                                ->where('c.isActivedInCart = :value')
                                ->andWhere('c.shippingmethod = :shippingmethod')
                                ->setParameter('value', true)
                                ->setParameter('shippingmethod', $shippingMethod)
                                ->orderBy('c.firstname', 'ASC');
                        },
                    ]);

            }else{

                $builder
                    ->add('deliveryAddress', EntityType::class, [
                        'class' => Address::class,
                        'label' => false,
                        'attr' => [
                            'class' => 'form-control text-center col-12',
                        ],
                        'placeholder' => '-- Adresse de livraison --',
                        'mapped' => false,
                        'multiple' => false,
                        'expanded' => true,
                        'query_builder' => function (EntityRepository $er) use ($user) {
                            return $er->createQueryBuilder('a')
                                ->where('a.isFacturation = :value')
                                ->andWhere('a.user = :user')
                                ->setParameter('user', $user)
                                ->setParameter('value', false)
                                ->orderBy('a.id', 'ASC');
                        },
                    ]);
            }

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
            'user' =>  null,
            'shippingMethodId' => null,
            'is_france' => true,
        ]);
    }
}
