<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Country;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email:',
                'attr' => [
                    'class' => 'form-control mb-3'
                ] 
            ])
            // ->add('phone', TelType::class, [
            //     'label' => 'Téléphone:',
            //     'constraints' => [
            //         new NotBlank([
            //             'message' => 'Téléphone obligatoire !',
            //         ]),
            //         new Regex([
            //             'pattern' => '/^[0-9]\d*$/',
            //             'message' => 'Téléphone: que des chiffres...'
            //         ])
            //     ],
            //     'attr' => [
            //         'class' => 'form-control mb-3'
            //     ]
            // ])
            ->add('country', EntityType::class, [
                'label' => 'Pays:',
                'class' => Country::class,
                'placeholder' => 'Choisir un pays...',
                'query_builder' => function (EntityRepository $c) {
                    return $c->createQueryBuilder('c')
                        ->where('c.actifInInscriptionForm = :value')
                        ->setParameter('value', true)
                        ->orderBy('c.name','ASC');
                },
                'attr' => [
                    'class' => 'form-control mb-3'
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'label' => 'Mot de passe:',
                'help' => 'Minimum 8 caractères',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password','class' => 'form-control'],
                'constraints' => [
                    new NotBlank(
                        message: 'Mot de passe obligatoire !',
                    ),
                    new Length(
                        min: 8,
                        minMessage: 'Votre mot de passe doit faire minimum {{ limit }} charactères',
                        // max length allowed by Symfony for security reasons
                        max: 4096,
                    ),
                ],
            ])
            ->add('plainPasswordVerification', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'label' => 'Vérification du mot de passe:',
                'mapped' => false,
                'help' => 'Minimum 8 caractères',
                'attr' => ['autocomplete' => 'new-password','class' => 'form-control'],
                'constraints' => [
                    new NotBlank(
                        message: 'Mot de passe obligatoire !',
                    ),
                    new Length(
                        min: 8,
                        minMessage: 'Votre mot de passe doit faire minimum {{ limit }} charactères',
                        // max length allowed by Symfony for security reasons
                        max: 4096,
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
