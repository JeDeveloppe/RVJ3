<?php

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;
use App\Entity\Address;
use App\Entity\QuoteRequest;
use App\Entity\CollectionPoint;
use App\Repository\TaxRepository;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\ShippingMethodRepository;
use App\Repository\CollectionPointRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\QuoteRequestLineRepository;
use App\Repository\DocumentParametreRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class QuoteRequestService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DocumentParametreRepository $documentParametreRepository,
        private PanierService $panierService,
        private Security $security,
        private TaxRepository $taxRepository,
        private UtilitiesService $utilitiesService,
        private QuoteRequestLineRepository $quoteRequestLineRepository,
        private AddressRepository $addressRepository,
        private ShippingMethodRepository $shippingMethodRepository,
        private CollectionPointRepository $collectionPointRepository,
        private RequestStack $requestStack
        ){
    }

    public function setDonneesInSessionForDocumentGeneration(QuoteRequest $quoteRequest, Request $request)
    {
        $session = $request->getSession();

        if($quoteRequest->getCollectionPoint() != null){
            $sessionInfoForDocument = [
            'deliveryAddressId' => $quoteRequest->getCollectionPoint()->getId(),
            'billingAddressId' => $quoteRequest->getBillingAddress()->getId(),
            'shippingMethodId' => $quoteRequest->getShippingMethod()->getId(),
            // 'voucherDiscountId' => $quoteRequest->getVoucherDiscount()->getId(),
            ];
        }else{

            $sessionInfoForDocument = [
            'deliveryAddressId' => $quoteRequest->getDeliveryAddress()->getId(),
            'billingAddressId' => $quoteRequest->getBillingAddress()->getId(),
            'shippingMethodId' => $quoteRequest->getShippingMethod()->getId(),
            // 'voucherDiscountId' => $quoteRequest->getVoucherDiscount()->getId(),
            ];
        }

        $session->set('paniers', $sessionInfoForDocument);
    }

    public function setDetailsPanierForDocumentGeneration(QuoteRequest $quoteRequest)
    {

        $totalPriceExcludingTax = 0;
        $totalWeight = 0;
        $totalPriceExcludingTaxOnlyPieces = 0;
   
        foreach ($quoteRequest->getQuoteRequestLines() as $line) {
            // Calculate totals for display, irrespective of submission
            $totalPriceExcludingTaxOnlyPieces += $line->getPriceExcludingTax();
            $totalWeight += $line->getWeight();
        }

        // Calculate delivery cost and total price based on current state of all lines
        if($this->requestStack->getSession()->has('deliveryCost')) {

            $deliveryCost = $this->requestStack->getSession()->get('deliveryCost');

        }else{

            $deliveryCost = $this->panierService->returnDeliveryCost($quoteRequest->getShippingMethod(), $totalWeight, $quoteRequest->getUser());
        }

        //?gratuit pour les structures adhérentes
        $preparationHt = 0;

        $totalPriceExcludingTax = $totalPriceExcludingTaxOnlyPieces + $deliveryCost + $preparationHt;

        //TODO de facon dynamique pour remise
        $donnees = [
            'preparationHt' => $preparationHt,
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

    public function deleteQrl(int $quoteRequestId, int $id): bool
    {
        $user = $this->security->getUser();
        $quoteRequestLine = $this->quoteRequestLineRepository->findQuoteRequestLineToDelete($user, $quoteRequestId, $id);

        if(!$quoteRequestLine) {
            throw new \Exception('QuoteRequestLine not found');
            return false;

        }else{

            $this->em->remove($quoteRequestLine);
            $this->em->flush();

            return true;
        }
    }

    public function testIfBillingAndDeliveryAdressesAreFromTheUserAndSaveInQuoteRequest(QuoteRequest $quoteRequest, int $billingAdressId, int $deliveryAdressId, int $shippingMethodId): bool
    {
        $formOk = false;
        $billingAddress = $this->addressRepository->findOneBy(['id' =>  $billingAdressId, 'user' => $this->security->getUser(), 'isFacturation' => true ]);

        if(!$billingAddress){
            $formOk = false;
        }else{
            $formOk = true;
            $quoteRequest->setBillingAddress($billingAddress);
        }

        $shippingMethod = $this->shippingMethodRepository->findOneBy(['id' => $shippingMethodId]);
        if(!$shippingMethod){
            $formOk = false;

        }else{
            $formOk = true;

            $quoteRequest->setShippingMethod($shippingMethod);

            if($shippingMethod->getPrice() == 'GRATUIT'){

                $collectionPoint = $this->collectionPointRepository->findOneById($deliveryAdressId);
                if(!$collectionPoint){
                    $formOk = false;
                }else{
                    $formOk = true;
                    $quoteRequest->setCollectionPoint($collectionPoint);
                    $quoteRequest->setDeliveryAddress(null);
                }

            }else{

                $deliveryAddress = $this->addressRepository->findOneBy(['id' => $deliveryAdressId, 'user' => $this->security->getUser(), 'isFacturation' => false]);
                if(!$deliveryAddress){
                    $formOk = false;
                }else{
                    $formOk = true;
                    $quoteRequest->setDeliveryAddress($deliveryAddress);
                    $quoteRequest->setCollectionPoint(null);
                }
            }


            $this->em->persist($quoteRequest);
            $this->em->flush();
            
        }

        return $formOk;
    }
}