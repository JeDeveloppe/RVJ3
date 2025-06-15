<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Boite;
use DateInterval;
use DateTimeZone;
use App\Entity\User;
use App\Entity\Panier;
use DateTimeImmutable;
use App\Entity\QuoteRequest;
use App\Entity\QuoteRequestLine;
use App\Repository\AddressRepository;
use App\Repository\TaxRepository;
use App\Repository\ItemRepository;
use App\Repository\UserRepository;
use App\Repository\CountryRepository;
use App\Repository\PanierRepository;
use App\Repository\DeliveryRepository;
use App\Repository\DiscountRepository;
use App\Repository\OccasionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SiteSettingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\ShippingMethodRepository;
use App\Repository\VoucherDiscountRepository;
use App\Repository\DocumentParametreRepository;
use App\Repository\QuoteRequestRepository;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

class PanierService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SiteSettingRepository $siteSettingRepository,
        private DiscountRepository $discountRepository,
        private OccasionRepository $occasionRepository,
        private ItemRepository $itemRepository,
        private ShippingMethodRepository $shippingMethodRepository,
        private PanierRepository $panierRepository,
        private DeliveryRepository $deliveryRepository,
        private UtilitiesService $utilitiesService,
        private TaxRepository $taxRepository,
        private DocumentParametreRepository $documentParametreRepository,
        private Security $security,
        private RequestStack $request,
        private UserRepository $userRepository,
        private VoucherDiscountRepository $voucherDiscountRepository,
        private CountryRepository $countryRepository,
        private RequestStack $requestStack,
        private QuoteRequestRepository $quoteRequestRepository,
        private AddressRepository $addressRepository
        ){
    }

    public function addOccasionInCartRealtime(int $occasion_id, int $qte)
    {

        $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);

        $tokenSession = $this->request->getSession()->get('tokenSession');
        $user = $this->security->getUser();

        if(!$user){

            $user = $this->userRepository->findOneByEmail($_ENV['UNDEFINED_USER_EMAIL']);
        }

        //?on supprimer les paniers de plus de x heures
        $this->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

        $occasion = $this->occasionRepository->findOneBy(['id' => $occasion_id, 'isOnline' => true]);

        if(!$occasion){

            $reponse = ['warning', 'Occasion inconnu ou déjà réservé !'];

        }else{

            $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
            $delay = $docParams->getDelayToDeleteCartInHours() ?? 2;
            $endPanier = $now->add(new DateInterval('PT'.$delay.'H'));

            $panier = new Panier();
            $panier->setOccasion($occasion);
            $panier->setQte($qte);
            $panier->setCreatedAt($endPanier);
            $panier->setPriceWithoutTax($occasion->getPriceWithoutTax() * $qte);
            $panier->setUnitPriceExclusingTax($occasion->getPriceWithoutTax());
            $panier->setTokenSession($tokenSession);
            $panier->setUser($user);
            $this->em->persist($panier);

            $occasion->setIsOnline(false);
            $this->em->persist($occasion);

            $this->em->flush();

            $reponse = ['success', 'Jeu ajouté au panier'];
        }

        return $reponse;
    }

    public function deleteCartLineRealtime(int $cart_id)
    {

        $session = $this->request->getSession();
        $tokenSession = $session->get('tokenSession');
        $user = $this->security->getUser();

        if(!$user){    

            $panier = $this->panierRepository->findOneBy(['id' => $cart_id, 'tokenSession' => $tokenSession]);
            
        }else{
            
            $panier = $this->panierRepository->findOneBy(['id' => $cart_id, 'user' => $user]);
        }

        if(!$panier){

            $reponse = ['warning', 'Ligne de panier inconnue !'];

        }else{

            if(!is_null($panier->getItem())){

                $item = $panier->getItem();
                $item->setStockForSale($item->getStockForSale() + $panier->getQte());

            }
            if(!is_null($panier->getOccasion())){

                $panier->getOccasion()->setIsOnline(true);
            }

            $this->em->remove($panier);

            $this->em->flush();

            $reponse = ['success', 'Ligne supprimée du panier'];
        }

        return $reponse;
    }

    public function addArticleInCartRealtime($article_id,$qte)
    {
        $tokenSession = $this->request->getSession()->get('tokenSession');
        $item = $this->itemRepository->findOneBy(['id' => $article_id]);
        $user = $this->security->getUser();

        if(!$user){

            $user = $this->userRepository->findOneByEmail($_ENV['UNDEFINED_USER_EMAIL']);
        }

        //?on supprimer les paniers de plus de x heures
        $this->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

        //?si pas d'article connu
        if(!$item){

            $reponse = ['warning', 'Article inconnu !'];

        }else{

            $stockDispo = $item->getStockForSale();

            if($stockDispo >= $qte){
                
                $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);
                $delay = $docParams->getDelayToDeleteCartInHours() ?? 2;

                $panier = $this->panierRepository->findOneBy(['tokenSession' => $tokenSession, 'item' => $item]);
    
                $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
                $endPanier = $now->add(new DateInterval('PT'.$delay.'H'));//TODO: changer pour 2h
                

                if($panier){

                    $panier->setQte($panier->getQte() + $qte)->setCreatedAt($endPanier)->setUser($user);
                    $this->em->persist($panier);

                }else{

                    $panier = new Panier();
                    $panier->setItem($item);
                    $panier->setQte($qte);
                    $panier->setPriceWithoutTax($item->getPriceExcludingTax() * $qte);
                    $panier->setUnitPriceExclusingTax($item->getPriceExcludingTax());
                    $panier->setCreatedAt($endPanier); //on rajoute x heure pour suppression
                    $panier->setTokenSession($tokenSession);
                    $panier->setUser($user);
                    $this->em->persist($panier);
                }
                
                $item->setStockForSale($item->getStockForSale() - $qte);
                $this->em->persist($item);
                
                $this->em->flush();
                $reponse = ['success', 'Article(s) ajouté au panier'];

            }else{

                $reponse = ['warning', 'Quantité insuffisante !'];

            }
        }

        return $reponse;
    }

    public function returnArrayWithAllCounts(): array
    {
        
        //toutes les variables seront dans un mega array
        $responses = [];
        //on recupere la session en cours
        $session = $this->request->getSession();
        //onn recupere l'utilisateur
        $user = $this->security->getUser();
        //on recupere des valeurs en session concernant le panier
        $panierInSession = $session->get('paniers',[]);
        //on cherche les paniers de l'utilisateur
        $paniers = $this->returnAllPaniersFromUser();

        // IL FAUT QUELQUE VARIABLES
        //les parametres des documents
        $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);
        //init le cout de la preparation des articles
        $responses['preparationHt'] = $docParams->getPreparation();
        //init gestion du memberShip (version precedente)
        $responses['memberShipOnTime'] = false;
        //init remise de volume
        $responses['remises']['volume'] = 0;
        //la date du jour
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        //on recupere l'entité taxe
        $responses['tax'] = $this->taxRepository->findOneBy([]);

        
        //si on est loguer
        if($user){
            
            //gestion membership au niveau du panier
            if($user->getMembership() > $now){
                $responses['preparationHt'] = 0;
                $responses['memberShipOnTime'] = true;
            }
            
        }else{
            
            $responses['remises']['volume'] = $this->calculateRemise($paniers);
            
        }
        
        
        //?ON CALCULE LE NOMBRE DE PANIERS PAR CATEGORIES
        $responses['panier_items'] = [];
        $responses['panier_occasions'] = [];
        $responses['panier_boites'] = [];
        foreach($paniers as $panier){
            if(!empty($panier->getItem())){
                $responses['panier_items'][] = $panier;
            }
            if(!empty($panier->getOccasion())){
                $responses['panier_occasions'][] = $panier;
            }
            if(!empty($panier->getBoite())){
                $responses['panier_boites'][] = $panier;
            }
        }

        //? FRAIS DE PREPARATION S'IL N'Y A PAS D'ARTICLES
        if(count($responses['panier_items']) < 1 && count($responses['panier_boites']) < 1){
            $responses['preparationHt'] = 0;
        }

        //? CALCUL DE LA REMISE SI UN CODE EST RENSEIGNER
        $responses['remises']['voucher']['voucherMax'] = 0;
        $responses['remises']['voucher']['actif'] = false;
        
        if(!is_null($panierInSession['voucherDiscountId'])){
            $voucherDiscount = $this->voucherDiscountRepository->find($panierInSession['voucherDiscountId']);
            $responses['remises']['voucher']['voucherMax'] = $voucherDiscount->getRemainingValueToUseExcludingTax();
            $responses['remises']['voucher']['token'] = $voucherDiscount->getToken();
            $responses['remises']['voucher']['actif'] = true;
        }
        
        //? CALCUL DES TOTAUX
        $responses['totauxItems'] = $this->utilitiesService->totauxByPanierGroup($responses['panier_items']);
        $responses['totauxOccasions'] = $this->utilitiesService->totauxByPanierGroup($responses['panier_occasions']);
        $responses['totauxBoites'] = $this->utilitiesService->totauxByPanierGroup($responses['panier_boites']);
        $responses['weigthPanier'] = $responses['totauxBoites']['weigth'] + $responses['totauxOccasions']['weigth'] + $responses['totauxItems']['weigth'];
        $weigthPanier = $responses['weigthPanier'];

        // $shippingMethodId = $session->get('shippingMethodId');
        $shippingMethodId = $this->requestStack->getCurrentRequest()->cookies->get('shippingMethodId');       

        //?si pas de methode de livraison en session ou si y a un occasion dans le panier, retrait à caen obligatoire
        if(!$shippingMethodId OR count($responses['panier_occasions']) > 0){
            $shippingMethodRetraitInCaen = $this->shippingMethodRepository->findOneByName($_ENV['SHIPPING_METHOD_BY_IN_RVJ_DEPOT_NAME']);
            $shippingMethodId = $shippingMethodRetraitInCaen->getId();
        }

        $session->set('shippingMethodId', $shippingMethodId);
        $responses['shippingMethodId'] = $shippingMethodId;
        $responses['deliveryCostWithoutTax'] = $this->returnDeliveryCost($shippingMethodId, $weigthPanier, $this->security->getUser());


        //? calcul de la remise sur les articles
        $responses['remises']['volume'] = $this->calculateRemise($this->panierRepository->findBy(['user' => $user]));

        // $responses['remises']['volume']['remiseDeQte'] = round($responses['totauxItems']['price'] * $responses['remises']['volume']['value'] / 100);
        $responses['remises']['volume']['remiseDeQte'] = 0; //?desactiver pour le moment
        
        $sousTotalItemHTAfterRemiseVolume = $responses['totauxItems']['price'] - $responses['remises']['volume']['remiseDeQte'];
        $totalHTItemsAndBoite = round(($responses['totauxItems']['price'] + $responses['totauxBoites']['price'] + $responses['totauxOccasions']['price']) - $responses['remises']['volume']['remiseDeQte']);

        //? calcule de la remise sur les articles
        $diff = $totalHTItemsAndBoite - $responses['remises']['voucher']['voucherMax'];

        if($diff >= 0){
            $responses['remises']['voucher']['used'] = $totalHTItemsAndBoite - $diff;
            $responses['remises']['voucher']['voucherRemaining'] = 0; // reste à utilisé du bon
        }else{
            $responses['remises']['voucher']['used'] = $totalHTItemsAndBoite;
            $responses['remises']['voucher']['voucherRemaining'] = $diff * -1; // reste à utilisé du bon
        }



        $responses['totalPanierHtBeforeDelivery'] = ($responses['preparationHt'] + $responses['totauxItems']['price'] + $responses['totauxBoites']['price'] + $responses['totauxOccasions']['price']) - $responses['remises']['volume']['remiseDeQte'] - $responses['remises']['voucher']['used'];
        $responses['totalPanierHtAfterDelivery'] = $responses['totalPanierHtBeforeDelivery'] + $responses['deliveryCostWithoutTax'];
        
        return $responses;

    }

    public function calculateRemise($paniers)
    {

        $qte = 0;
        foreach($paniers as $panier){
            //?remise que sur les articles
            if(!empty($panier->getItem())){
                $qte += $panier->getQte();
            }
        }

        $discount = $this->discountRepository->findDiscountPercentage($qte);
    
        if($discount)
        {
            $remises['actif'] = true;
            $remises['qte'] = $qte;
            $remises['value'] = $discount->getValue();
            $remises['nextQteForRemiseSupplementaire'] = $discount->getEnd() + 1;
            $nextRemise = $this->discountRepository->findDiscountPercentage($remises['nextQteForRemiseSupplementaire']);

            if($nextRemise)
            {
                $remises['nextRemiseSupplementaire'] = $nextRemise->getValue();

            }else{

                $remises['nextRemiseSupplementaire'] = false;
            }

        }else{

            $remises['actif'] = false;
            $remises['qte'] = 0;
            $remises['value'] = 0;
            $remises['nextQteForRemiseSupplementaire'] = 0;
            $remises['nextRemiseSupplementaire'] = 0;
        }
        

        return $remises;
    }

    public function checkSessionForSaveInDatabase($panierInSession)
    {

        $validationKO = [];

        $stringVariablesToCheckIfThereExists = ['voucherDiscountId','billingAddressId','deliveryAddressId','shippingMethodId'];

        foreach($stringVariablesToCheckIfThereExists as $stringVariablesToCheckIfThereExist){
            if(!array_key_exists($stringVariablesToCheckIfThereExist, $panierInSession)){
                $validationKO[] = $stringVariablesToCheckIfThereExist;
            }
        }

        if(count($validationKO) > 0){
            dd('Variables manquantes dans la session pour valider le panier: '. var_dump($validationKO));
        }
    }

    public function returnDeliveryCost($shippingId, int $weigthPanier, User $user)
    {
        $shippingMethod = $this->shippingMethodRepository->find($shippingId);

        if(!$user){
            $france = $this->countryRepository->findOneBy(['name' => 'FRANCE']);
            $user = new User();
            $user->setCountry($france);
            $delivery = $this->deliveryRepository->findCostByDeliveryShippingMethod($shippingMethod, $weigthPanier, $user);

        }else{

            $delivery = $this->deliveryRepository->findCostByDeliveryShippingMethod($shippingMethod, $weigthPanier, $user);
        }

        $result = $delivery->getPriceExcludingTax();

        return $result;
    }

    public function addBoiteRequestToCart(HttpFoundationRequest $request, Boite $boite)
    {

        $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);
        $delay = $docParams->getDelayToDeleteCartInHours() ?? 2;
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        $endPanier = $now->add(new DateInterval('PT'.$delay.'H'));//TODO: changer pour 2h
        $allPostData = $request->request->all();
        $message = $allPostData['request_for_box']['message'];

        $panier = new Panier();
        $panier
            ->setBoite($boite)
            ->setQuestion($message)
            ->setCreatedAt($endPanier)
            ->setUser($this->security->getUser())
            ->setQte(1)
            ->setTokenSession($this->request->getSession()->get('tokenSession'));
        $this->em->persist($panier);
        $this->em->flush();
    }
    // public function separateBoitesItemsAndOccasion(array $paniers): array
    // {

    //     //responses['panier_boites'], $responses['panier_items'], $responses['panier_occasions']

    //     $responses['panier_occasions'] = [];
    //     $responses['panier_items'] = [];
    //     $responses['panier_boites'] = [];

    //     foreach($paniers as $panier){
    //         if(!is_null($panier->getOccasion())){
    //             $responses['panier_occasions'][] = $panier;
    //         }

    //         if(!is_null($panier->getItem())){
    //             $responses['panier_items'][] = $panier;
    //         }

    //         if(!is_null($panier->getBoite())){
    //             $responses['panier_boites'][] = $panier;
    //         }

    //     }

    //     return $responses;
    // }

    public function returnAllPaniersFromUser()
    {

        $session = $this->request->getSession();
        $tokenSession = $session->get('tokenSession');
        $user = $this->security->getUser();
        

        //si on est pas identifier on cherche les paniers de la session
        $paniersA = $this->panierRepository->findBy(['tokenSession' => $tokenSession]) ?? []; //les paniers 

        if($user){

            $paniersA = $this->panierRepository->findBy(['user' => $user]) ?? []; //les paniers 

        }

        return $paniersA;
    }

    public function deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock()
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        $paniersToDelete = $this->panierRepository->findPaniersToDeleteWhenEndOfValidationIsToOld($now);

        foreach($paniersToDelete as $panier)
        {

            if(!is_null($panier->getItem())){
                $itemInDatabase = $this->itemRepository->find($panier->getItem());
                $itemInDatabase->setStockForSale($itemInDatabase->getStockForSale() + $panier->getQte());
                $this->em->persist($itemInDatabase);
                $this->em->remove($panier);
            }

            if(!is_null($panier->getOccasion())){
                $occasionInDatabase = $this->occasionRepository->find($panier->getOccasion());
                $occasionInDatabase->setIsOnline(true);
                $this->em->persist($occasionInDatabase);
                $this->em->remove($panier);
            }

            $this->em->remove($panier);
        }
        
        $this->em->flush();
    }

    public function deleteVoucherFromCart()
    {
        $panierInSession = $this->request->getSession()->get('paniers', []);
        $panierInSession['voucherDiscountId'] = NULL;
        $this->request->getSession()->set('paniers', $panierInSession);
    }

    public function saveDemandesInDatabase(array $paniers, array $donnees): QuoteRequest
    {
        $quoteRequest = new QuoteRequest();
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));

        $quoteRequest
            ->setCreatedAt($now)
            ->setIsSendByEmail(false)
            ->setShippingMethod($this->shippingMethodRepository->findOneById($donnees['shippingMethodId']))
            ->setDeliveryAddress($this->addressRepository->findOneById($donnees['deliveryAddressId']))
            ->setBillingAddress($this->addressRepository->findOneById($donnees['billingAddressId']))
            ->setUser($this->security->getUser())
            ->setUuid(Uuid::v4());
        $this->em->persist($quoteRequest);
        $this->em->flush();


        foreach($paniers as $panier){

            $quoteRequestDetail = new QuoteRequestLine();
            $quoteRequestDetail
                ->setQuoteRequest($quoteRequest)
                ->setQuestion($panier->getQuestion())
                ->setBoite($panier->getBoite())
                ->setPriceExcludingTax(0);
            $this->em->persist($quoteRequestDetail);

            //?on supprime le panier
            $this->deleteCartLineRealtime($panier->getId());

        }
        $this->em->flush();

        return $quoteRequest;
    }
}