<?php

namespace App\Service;

use App\Entity\QuoteRequest;
use App\Repository\DocumentParametreRepository;
use App\Repository\TaxRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class QuoteRequestService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DocumentParametreRepository $documentParametreRepository,
        private PanierService $panierService,
        private TaxRepository $taxRepository,
        private UtilitiesService $utilitiesService
        ){
    }

    public function setDonneesInSessionForDocumentGeneration(QuoteRequest $quoteRequest, Request $request)
    {
        $session = $request->getSession();
        $sessionInfoForDocument = [
        'deliveryAddressId' => $quoteRequest->getDeliveryAddress()->getId(),
        'billingAddressId' => $quoteRequest->getBillingAddress()->getId(),
        'shippingMethodId' => $quoteRequest->getShippingMethod()->getId(),
        // 'voucherDiscountId' => $quoteRequest->getVoucherDiscount()->getId(),
        ];
        $session->set('paniers', $sessionInfoForDocument);
    }

    public function setDetailsPanierForDocumentGeneration(QuoteRequest $quoteRequest)
    {

        $totalPriceExcludingTax = 0;
        $totalWeight = 0;
        $totalPriceExcludingTaxOnlyPieces = 0;
        $params = $this->documentParametreRepository->findOneBy([]);
   
        foreach ($quoteRequest->getQuoteRequestLines() as $line) {
            // Calculate totals for display, irrespective of submission
            $totalPriceExcludingTaxOnlyPieces += $line->getPriceExcludingTax();
            $totalWeight += $line->getWeight();
        }

        // Calculate delivery cost and total price based on current state of all lines
  
        $deliveryCost = $this->panierService->returnDeliveryCost($quoteRequest->getShippingMethod(), $totalWeight, $quoteRequest->getUser());
        $preparationHt = $this->documentParametreRepository->findOneBy([])->getPreparation();

        $totalPriceExcludingTax = $totalPriceExcludingTaxOnlyPieces + $deliveryCost + $preparationHt;

        //TODO de facon dynamique pour remise
        $donnees = [
            'preparationHt' => $params->getPreparation(),
            'memberShipOnTime' => false,
            'remises' => [
                'volume' => [
                    'actif' => false,
                    'qte' => 0,
                    'value' => 0,
                    'nextQteForRemiseSupplementaire' => 0,
                    'nextRemiseSupplementaire' => 0,
                    'remiseDeQte' => 0
                ],
                'voucher' => [
                    'voucherMax' => 0,
                    'actif' => false,
                    'used' => 0.0,
                    'voucherRemaining' => 0
                ]
            ],
            'tax' => $this->taxRepository->findOneBy([]),
            'panier_items' => [],
            'panier_occasions' => [],
            'totauxBoites' => [
                'weigth' => $totalWeight,
                'price' => $totalPriceExcludingTaxOnlyPieces
            ],
            'totauxItems' => [
                'weigth' => 0,
                'price' => 0
            ],
            'totauxOccasions' => [
                'weigth' => 0,
                'price' => 0
            ],
            'panier_boites' => $quoteRequest->getQuoteRequestLines(),
            'weightPanier' => $totalWeight,
            'shippingMethodId' => $quoteRequest->getShippingMethod()->getId(),
            'deliveryCostWithoutTax' => $deliveryCost,
            'totalPanierHtBeforeDelivery' => $totalPriceExcludingTaxOnlyPieces,
            'totalPanierHtAfterDelivery' => $totalPriceExcludingTax
        ];
        return $donnees;

    }

    public function generateValuesForQRview(QuoteRequest $quoteRequest):array
    { 
        $results = [];

        $results['tauxTva'] = $this->utilitiesService->calculTauxTva($this->taxRepository->findOneBy([])->getValue());

        $results['docLines_items'] = [];
        $results['docLines_occasions'] = [];
        $results['docLines_boites'] = $quoteRequest->getQuoteRequestLines()->toArray();
        
        return $results;
    }

}