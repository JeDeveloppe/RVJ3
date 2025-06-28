<?php

namespace App\Controller\Site;

use App\Form\VoucherType;
use App\Form\ShippingType;
use App\Form\AcceptCartType;
use App\Service\PanierService;
use App\Service\DocumentService;
use App\Repository\TaxRepository;
use App\Service\UtilitiesService;
use App\Repository\ItemRepository;
use App\Repository\BoiteRepository;
use App\Repository\PanierRepository;
use App\Repository\AddressRepository;
use App\Repository\DeliveryRepository;
use App\Repository\DocumentRepository;
use App\Repository\OccasionRepository;
use App\Form\BillingAndDeliveryAddressType;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\ShippingMethodRepository;
use App\Repository\CollectionPointRepository;
use App\Repository\DocumentParametreRepository;
use App\Repository\VoucherDiscountRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Twig\Environment;

class PanierController extends AbstractController
{
    public function __construct(
        private RequestStack $request,
        private PanierService $panierService,
        private PanierRepository $panierRepository,
        private AddressRepository $addressRepository,
        private Security $security,
        private ShippingMethodRepository $shippingMethodRepository,
        private CollectionPointRepository $collectionPointRepository,
        private DeliveryRepository $deliveryRepository,
        private BoiteRepository $boiteRepository,
        private DocumentService $documentService,
        private ItemRepository $itemRepository,
        private DocumentRepository $documentRepository,
        private VoucherDiscountRepository $voucherDiscountRepository,
        private OccasionRepository $occasionRepository,
        private TaxRepository $taxRepository,
        private UtilitiesService $utilitiesService,
        private DocumentParametreRepository $documentParametreRepository,
        private Environment $twig
    )
    {
    }

    #[Route('/panier', name: 'panier_start')]
    public function index(Request $request): Response
    {
        //?on supprimer les paniers de plus de x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();
        
        //?on demarre la session
        $session = $request->getSession();
        
        //?on garde en memoire back_url_after_login
        $panierInSession = $session->get('paniers', []);
        
        if(!array_key_exists('voucherDiscountId', $panierInSession)){
            $panierInSession['voucherDiscountId'] = NULL; // on initialise à null
            $session->set('paniers', $panierInSession);
        }
        
        if(!array_key_exists('back_url_after_login', $panierInSession)){
            
            $panierInSession['back_url_after_login'] = $request->get('_route');
            $session->set('paniers', $panierInSession);
        }
        
        //?on recupere les paniers de l'utilisateur
        $paniers = $this->panierService->returnAllPaniersFromUser();
        
        //?retour en arriere si panier vide
        if(count($paniers) < 1){
            
            $this->addFlash('warning', 'Votre panier est vide !');
            
            return $this->redirectToRoute('app_home');
            
        }
        
        //?on calcule les valeurs du panier
        $allCartValues = $this->panierService->returnArrayWithAllCounts();

        //on met a jour le cookie
        $request->cookies->set('shippingMethodId', $allCartValues['shippingMethodId']);

        $shippingForm = $this->createForm(ShippingType::class, null, ['occasionInPanier' => $allCartValues['panier_occasions']]);
        $shippingForm->handleRequest($request);

        //form pour les codes de reduction
        $voucherType = $this->createForm(VoucherType::class);
        $voucherType->handleRequest($request);
        

        if($voucherType->isSubmitted() && $voucherType->isValid())
        {

            $voucherDiscountCode = $voucherType['voucherDiscount']->getData();
            // $shippingMethodId = $shippingAndVoucherForm['shipping']->getData()->getId();

            if(is_null($voucherDiscountCode)){

                $voucherDiscountId = null;

            }else{

                //on enlève les espaces au cas ou
                $voucherDiscountCode = str_replace(' ','', $voucherDiscountCode);
                $voucherDiscount = $this->voucherDiscountRepository->findOneVoucherIsActive($voucherDiscountCode);

                if(is_null($voucherDiscount)){

                    $this->addFlash('warning', 'Bon d\'achat inconnu !');
                    $voucherDiscountId = null;

                }else{

                    $voucherDiscountId = $voucherDiscount->getId();
                    $this->addFlash('success', 'Bon d\'achat reconnu !');

                }

            }

            $panierInSession['voucherDiscountId'] = $voucherDiscountId;
            $session->set('paniers', $panierInSession);

            //et on recalcul le tout
            $allCartValues = $this->panierService->returnArrayWithAllCounts();

        }

        $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);
        if($docParams->getDelayToDeleteCartInHours() == NULL){
            $docParams->setDelayToDeleteCartInHours(2);
        }

        return $this->render('site/pages/panier/panier.html.twig', [
            'voucherDiscountForm' => $voucherType,
            'shippingForm' => $shippingForm,
            'allCartValues' => $allCartValues,
            'docParams' => $docParams
        ]);
    }

    #[Route('/panier/choix-des-adresses', name: 'panier_addresses')]
    public function cartAddresses(Request $request): Response
    {
        //?on supprimer les paniers de plus de x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

        //?on demarre la session
        $session = $request->getSession();
        $panierInSession = $session->get('paniers', []);

        //on cherche le cookie obligatoire
        $shippingethodIdInCookie = $request->cookies->get('shippingMethodId');

        if(!$shippingethodIdInCookie){

            $this->addFlash('warning', 'ShippingethodIdInCookie absent');

            return $this->redirectToRoute('panier_start');

        }

        $panierInSession = $session->get('paniers', []);

        //?on compte le nombre de products dans le panier en session
        $paniers = $this->panierService->returnAllPaniersFromUser();
        //?retour en arriere si panier vide
        if(count($paniers) < 1){

            $this->addFlash('warning', 'Votre panier est vide !');

            return $this->redirectToRoute('app_home');

        }

        //on recupere les infos du panier
        $allCartValues = $this->panierService->returnArrayWithAllCounts();

        $shippingMethod = $this->shippingMethodRepository->findOneById($allCartValues['shippingMethodId']);


        $billingAndDeliveryForm = $this->createForm(BillingAndDeliveryAddressType::class, null, [
            'user' => $this->security->getUser(),
            'shippingMethodId' => $shippingMethod,
        ]);

        $billingAndDeliveryForm->handleRequest($request);

        if($billingAndDeliveryForm->isSubmitted() && $billingAndDeliveryForm->isValid()){

            $formOk = true;
            $billingAddress = $billingAndDeliveryForm['billingAddress']->getData();
            $deliveryAddress = $billingAndDeliveryForm['deliveryAddress']->getData();

            if(!$billingAddress){
                $this->addFlash('warning', 'Aucune adresse de facturation choisie !');
                $formOk = false;
            }
            if(!$deliveryAddress){
                $this->addFlash('warning', 'Aucune adresse de livraison / retrait choisie !');
                $formOk = false;
            }

            if($formOk == false){

                //on redirige var la page précèdante
                return $this->redirect($request->headers->get('referer'));

            }else{

                //on met en session les address choisies
                $panierInSession['billingAddressId'] = $billingAddress->getId();
                $panierInSession['deliveryAddressId'] = $deliveryAddress->getId();
                $panierInSession['shippingMethodId'] = $session->get('shippingMethodId');

                $session->set('paniers', $panierInSession);
                //on redirige vers la page suivante
                return $this->redirectToRoute('panier_before_paiement');
            }

        }

        return $this->render('site/pages/panier/panier_addresses.html.twig', [
            'billingAndDeliveryForm' => $billingAndDeliveryForm,
            'voucherDiscountId' => $panierInSession['voucherDiscountId'],
            'allCartValues' => $allCartValues,
            'shippingMethod' => $shippingMethod,
        ]);
    }

    #[Route('/panier/verification-avant-paiement', name: 'panier_before_paiement')]
    public function cartEnd(Request $request): Response
    {

        //?on supprimer les paniers de plus de x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

        //?on demarre la session
        $session = $request->getSession();
        $panierInSession = $session->get('paniers', []);
        
        //on cherche le cookie obligatoire
        $shippingethodIdInCookie = $request->cookies->get('shippingMethodId');

        if(!$shippingethodIdInCookie){

            $this->addFlash('warning', 'ShippingethodIdInCookie absent');

            return $this->redirectToRoute('panier_start');

        }

        //?on compte le nombre de products dans le panier en session
        $paniers = $this->panierService->returnAllPaniersFromUser();

        //?retour en arriere si panier vide
        if(count($paniers) < 1){

            $this->addFlash('warning', 'Votre panier est vide !');

            return $this->redirectToRoute('app_home');

        }

        $billingAddressId = $panierInSession['billingAddressId'];
        $deliveryAddressId = $panierInSession['deliveryAddressId'];
        $shippingMethodId = $session->get('shippingMethodId');

        $shippingMethod = $this->shippingMethodRepository->findOneById($shippingMethodId);

        if($shippingMethod->getPrice() == 'GRATUIT'){

            $deliveryAddress = $this->collectionPointRepository->findOneById($deliveryAddressId);

        }else{

            $deliveryAddress = $this->addressRepository->findOneById($deliveryAddressId);
        }

        $billingAddress = $this->addressRepository->findOneById($billingAddressId);

        $acceptCartForm = $this->createForm(AcceptCartType::class);
        $acceptCartForm->handleRequest($request);

        //? toutes les infos du panier sont là
        $allCartValues = $this->panierService->returnArrayWithAllCounts();

        if($acceptCartForm->isSubmitted() && $acceptCartForm->isValid())
        {

            //?on vérifie si on a bien toutes les variables pour enregistrer le document
            $this->panierService->checkSessionForSaveInDatabase($panierInSession);
            //?on supprime les variables de session qui deviennent inutilisable
            $session->remove('back_url_after_login');
            $session->remove('shippingMethodId');
            //? sauvegarde document dans BDD avec articles, boites, etc... en fonction du Type DEVIS ou COMMANDE
            $document = $this->documentService->saveDocumentLogicInDataBase($allCartValues,$session,$request);

            //?on supprime le panier en session
            $session->remove('paniers');

            //paiement direct donc on redirige vers la page de paiement avec le numero de document
            return $this->redirectToRoute('paiement', ['tokenDocument' => $document->getToken()]);

        }

        return $this->render('site/pages/panier/panier_before_paiement.html.twig', [
            'acceptCartForm' => $acceptCartForm,
            'billingAddress' => $billingAddress,
            'deliveryAddress' => $deliveryAddress,
            'allCartValues' => $allCartValues
        ]);
    }

    #[Route('/panier/ajout-occasion/{occasion_id}/{qte}/', name: 'panier_add_occasion')]
    public function addOccasion(Request $request, $occasion_id, $qte): Response
    {

        $reponse = $this->panierService->addOccasionInCartRealtime($occasion_id, $qte);

        $this->addFlash($reponse[0], $reponse[1]);

        if($request->query->get('returnInCatalog')){

            return $this->redirect($request->query->get('returnInCatalog'));

        }else{

            return $this->redirect($request->headers->get('referer'));
        }

    }

    #[Route('/panier/faire-une-demande-de-prix/', name: 'panier_add_demande', methods: ['GET'])]
    public function addDemande(Request $request): Response
    {
        $paniersFromUser = $this->security->getUser()->getPaniers();
        $paniers = [];
        foreach($paniersFromUser as $demande){
            if($demande->getBoite() != null){
                $paniers[] = $demande;
            }
        }

        $donneesFromUser = $this->request->getSession()->get('paniers');

        if(count($paniers) < 1){

            $this->addFlash('warning', 'Aucune demande de prix enregistrée dans votre panier !');
            return $this->redirectToRoute('app_catalogue_switch');

        }else{
            
            $quoteRequest =$this->panierService->saveDemandesInDatabase($paniers, $donneesFromUser);

            if($quoteRequest->getId()){

                $this->addFlash('success', 'Demande de prix envoyée avec succès !');
                return $this->redirectToRoute('app_catalogue_switch');

            }else{

                $this->addFlash('warning', 'Erreur dans l\'envoie !');
                return $this->redirectToRoute('app_catalogue_switch');
            }
            
        }

    }

    public function checkUserIsConnected()
    {
        $user = $this->security->getUser();

        if(!$user){

            $this->addFlash('warning','Vous n\'êtes pas identifié.e');

            $this->redirectToRoute('app_home');
        }

        return $user;
    }

    #[Route('/panier/delete-cart-line/{cart_id}', name: 'delete_cart_line_realtime')]
    public function deleteCartLineRealtime($cart_id, Request $request): Response
    {

        $reponse = $this->panierService->deleteCartLineRealtime($cart_id);
      
        // $request->cookies->remove('shippingMethodId');
    
        $this->addFlash($reponse[0], $reponse[1]);

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/panier/ajout-article/', name: 'panier_add_article_realtime')]
    public function addArticleRealtime(Request $request): Response
    {

        $reponse = $this->panierService->addArticleInCartRealtime($request->request->get('itemId'),$request->request->get('qte'));

        $this->addFlash($reponse[0], $reponse[1]);

        return $this->redirect($request->headers->get('referer'));

    }

    #[Route('/panier/success/{tokenDocument}', name: 'panier_success')]
    public function panierSuccess($tokenDocument):Response
    {

        $document = $this->documentRepository->findOneBy(['token' => $tokenDocument]);

        if(!$document){
            $this->addFlash('warning', 'Document inconnu!');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('site/panier/panier_success.html.twig', ['document' => $document]);
    }

    #[Route('/panier/calcul-delivery-cost/', name: 'panier_calcul_delivery_cost')]
    public function panierShippingMethod(Request $request):JsonResponse
    {
        $gets = $request->getContent();
        $datas = json_decode($gets, true);

        if(!array_key_exists('shippingMethodId', $datas)){
            $shippingMethod = $this->shippingMethodRepository->findOneByName($_ENV['SHIPPING_METHOD_BY_IN_RVJ_DEPOT_NAME']);
        }else{
            $shippingMethod = $this->shippingMethodRepository->findOneBy(['id' => $datas['shippingMethodId']]);
        }

        $result = $this->panierService->returnDeliveryCost($shippingMethod->getId(), $datas['weight'], $this->security->getUser());

        return new JsonResponse(
            ['deliveryCost' => $result],
            $status = 200,
            $headers = []
        );
    }

    #[Route('/panier/delete-voucher-from-cart/', name: 'panier_delete_voucher_from_cart')]
    public function panierDeleteVoucherFromCart(Request $request):Response
    {

        $this->panierService->deleteVoucherFromCart();

        return $this->redirect($request->headers->get('referer'));
    }
}