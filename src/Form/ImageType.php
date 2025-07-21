<?php

namespace App\Form;

use App\Entity\Image;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType; // Assurez-vous que cette ligne est correcte !

// Si vous avez accidentellement cette ligne, supprimez-la ou commentez-la :
// use Liip\ImagineBundle\Form\Type\ImageType; 

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('imageFile', VichImageType::class, [ // Assurez-vous d'utiliser VichImageType::class ici
                'required' => false,
                'allow_delete' => true, // Permet de supprimer l'image existante
                'download_uri' => true, // Affiche un lien pour télécharger l'image
                'image_uri' => true, // Affiche l'image directement (nécessite LiipImagineBundle pour le filtrage si utilisé, mais Vich le gère aussi pour l'affichage brut)
                'asset_helper' => true, // Utilise l'asset helper pour générer l'URL
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
        ]);
    }
}