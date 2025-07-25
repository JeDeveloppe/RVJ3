<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
// SUPPRIMEZ CETTE LIGNE : use Liip\ImagineBundle\Form\Type\ImageType;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints as Assert;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Form\ImageType; // AJOUTEZ CETTE LIGNE pour importer votre formulaire ImageType

class RequestForBoxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextareaType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control col-8 mb-2 offset-2',
                    'rows' => 4,
                    'id' => 'message',
                    'minlength' => 5,
                    'maxlength' => 300],
                
            ])
             ->add('images', CollectionType::class, [
                'entry_type' => ImageType::class, // Utilisez bien votre App\Form\ImageType::class ici
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'entry_options' => ['label' => false],
                'constraints' => [
                    new Assert\Valid(),
                ],
            ])
            // ->add('captcha', Recaptcha3Type::class, [
            //     'constraints' => new Recaptcha3(),
            //     // 'action_name' => 'panier_add_demande',
            //     // 'script_nonce_csp' => $nonceCSP,
            //     'locale' => 'fr',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
