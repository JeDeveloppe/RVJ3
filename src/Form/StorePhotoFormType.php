<?php

namespace App\Form;

use App\Entity\StorePhoto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class StorePhotoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('photoFile', VichImageType::class, [
                'label' => 'Image (JPEG ou PNG)',
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
                'image_uri' => true, // Permet d'afficher l'URI de l'image
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StorePhoto::class,
        ]);
    }
}