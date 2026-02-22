<?php

namespace App\Form;

use App\Entity\City;
use App\Repository\CityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityAutocompleteField]
class AdressesVilleAutocompleteField extends AbstractType
{
    public function __construct(
        private Security $security
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        /** @var User */
        $user = $this->security->getUser();
        $userCountry = ($user && $user->getCountry()) ? $user->getCountry() : null;
        $placeholder = 'exemple: 14000 Caen';

        if ($userCountry && $userCountry->getIsoCode() !== 'FR') {
             $placeholder = 'Entrez votre ville';
        }

        $resolver->setDefaults([
            'class' => City::class,
            'label' => 'Ville:',
            'placeholder' => $placeholder,
            'choice_label' => function (City $city) {
                return $city->getPostalcode().' '.$city->getName() ;
            },
            'no_more_results_text' => 'PAS PLUS DE RESULTATS',
            // 'searchable_fields' => ['name','postalCode'],
            'query_builder' => function(CityRepository $cityRepository) use ($userCountry) {
                $qb = $cityRepository->createQueryBuilder('c');

                if ($userCountry) {
                    $qb->andWhere('c.country = :country')
                       ->setParameter('country', $userCountry);
                }

                return $qb;
            },
            //'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
