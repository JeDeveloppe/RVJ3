<?php

namespace App\Form;

use App\Entity\City;
use App\Repository\CityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class AdressesVilleAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => City::class,
            'label' => 'Ville:',
            'placeholder' => 'exemple: 14000 Caen',
            'choice_label' => function (City $city) {
                return $city->getPostalcode().' '.$city->getName() ;
            },
            'no_more_results_text' => 'PAS PLUS DE RESULTATS',
            // 'searchable_fields' => ['name','postalCode'],
            'query_builder' => function(CityRepository $cityRepository) {
                return $cityRepository->createQueryBuilder('c');
            },
            //'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
