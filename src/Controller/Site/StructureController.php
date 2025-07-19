<?php

namespace App\Controller\Site;

use DateTimeZone;
use DateTimeImmutable;
use App\Entity\ItemGroup;
use App\Entity\QuoteRequest;
use App\Form\AcceptCartType;
use App\Service\PanierService;
use App\Form\BoitesOrderByType;
use App\Form\RequestForBoxType;
use App\Service\AdresseService;
use App\Service\HistoryService;
use App\Entity\QuoteRequestLine;
use App\Service\OccasionService;
use App\Repository\TaxRepository;
use App\Service\CatalogueService;
use App\Service\UtilitiesService;
use App\Repository\ItemRepository;
use App\Repository\BoiteRepository;
use App\Repository\EditorRepository;
use App\Service\QuoteRequestService;
use App\Repository\AddressRepository;
use App\Repository\PartnerRepository;
use App\Repository\OccasionRepository;
use App\Repository\ItemGroupRepository;
use App\Form\SearchBoiteInCatalogueType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SiteSettingRepository;
use App\Service\CatalogControllerService;
use App\Repository\QuoteRequestRepository;
use App\Form\BillingAndDeliveryAddressType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Form\SearchOccasionsInCatalogueType;
use App\Repository\DurationOfGameRepository;
use App\Repository\ShippingMethodRepository;
use App\Repository\CollectionPointRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\QuoteRequestLineRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\QuoteRequestStatusRepository;
use App\Form\QuoteRequestChoiceShippingMethodType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\CatalogOccasionSearchRepository;
use App\Form\SearchOccasionNameOrEditorInCatalogueType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StructureController extends AbstractController
{

    public function __construct(
        private BoiteRepository $boiteRepository,
        private OccasionRepository $occasionRepository,
        private PaginatorInterface $paginator,
        private EditorRepository $editorRepository,
        private PartnerRepository $partnerRepository,
        private TaxRepository $taxRepository,
        private PanierService $panierService,
        private OccasionService $occasionService,
        private AddressRepository $addressRepository,
        private CollectionPointRepository $collectionPointRepository,
        private AdresseService $adresseService,
        private SiteSettingRepository $siteSettingRepository,
        private CatalogOccasionSearchRepository $catalogOccasionSearchRepository,
        private UtilitiesService $utilitiesService,
        private EntityManagerInterface $em,
        private Security $security,
        private CatalogueService $catalogueService,
        private DurationOfGameRepository $durationOfGameRepository,
        private RequestStack $requestStack,
        private CatalogControllerService $catalogControllerService,
        private ItemRepository $itemRepository,
        private ItemGroupRepository $itemGroupRepository,
        private QuoteRequestRepository $quoteRequestRepository,
        private QuoteRequestService $quoteRequestService,
        private QuoteRequestLineRepository $quoteRequestLineRepository,
        private QuoteRequestStatusRepository $quoteRequestStatusRepository,
        private ShippingMethodRepository $shippingMethodRepository,
        private HistoryService $historyService
    )
    {
    }
    
    
    #[Route('structure-adherente/catalogue-pieces-detachees', name: 'structure_catalogue_pieces_detachees')]
    public function cataloguePiecesDetacheesForStructure(Request $request): Response
    {
        $this->historyService->saveHistoryLogic();

        $countQuoteRequestLines = $this->quoteRequestLineRepository->countQuoteRequestLines($this->security->getUser());
        //?on supprimer les paniers de plus de x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

        $siteSetting = $this->siteSettingRepository->findOneBy([]);
        $activeTriWhereThereIsNoSearch = true;

        $form = $this->createForm(SearchBoiteInCatalogueType::class, null, ['method' => 'GET']);
        $form->handleRequest($request);

        $formTri = $this->createForm(BoitesOrderByType::class);
        $formTri->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $activeTriWhereThereIsNoSearch = false;
            $search = $form->get('search')->getData();
            $donnees = $this->boiteRepository->findBoitesForMemberStructure($search);

        }else{
            $orderBy = $request->query->get('orderColumn');
            $orders = ['name'];

            if($orderBy != null && in_array($orderBy, $orders)){
                $donnees = $this->boiteRepository->findBy(['isForAdherenteStructure' => true], [$orderBy => 'ASC']);
            }else{
                $donnees = $this->boiteRepository->findBy(['isForAdherenteStructure' => true], ['id' => 'DESC']);
            }

        }

        $boitesBox = $this->paginator->paginate(
            $donnees, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            12 /*limit per page*/
        );

        $metas['description'] = 'Catalogue complet de toutes les boites dont le service dispose de pièces détachées.';

        return $this->render('site/pages/structures/pieces_detachees_structures.html.twig', [
            'boitesBox' => $boitesBox,
            'allBoites' => count($donnees),
            'form' => $form,
            'countQuoteRequestLines' => $countQuoteRequestLines,
            'search' => $search ?? null,
            'activeTriWhereThereIsNoSearch' => $activeTriWhereThereIsNoSearch,
            'metas' => $metas,
            'forStructure' => true,
            'tax' => $this->taxRepository->findOneBy([]),
            'siteSetting' => $siteSetting,
            'formTri' => $formTri->createView()
        ]);
    }

    #[Route('structure-adherente/catalogue-pieces-detachees/demande/{id}/{editorSlug}/{boiteSlug}/', name: 'structure_catalogue_pieces_detachees_demande', requirements: ['boiteSlug' => '[a-z0-9\-]+'] )]
    public function cataloguePiecesDetacheesForStructureDemande(Request $request, $id, $editorSlug, $boiteSlug, $year = NULL, $search = NULL): Response
    {

        //?on supprimer les paniers de plus de x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();
        $countQuoteRequestLines = $this->quoteRequestLineRepository->countQuoteRequestLines($this->security->getUser());

        $boite = $this->boiteRepository->findOneBy(['id' => $id, 'isForAdherenteStructure' => true, 'slug' => $boiteSlug, 'editor' => $this->editorRepository->findOneBy(['slug' => $editorSlug])]);

        if(!$boite){
            $this->addFlash('warning', 'Boite inconnue');
            return $this->redirectToRoute('app_catalogue_pieces_detachees');
        }

        $yearInDescription = $boite->getYear();
        if($yearInDescription == 0){
            $yearInDescription = 'inconnue';
        }
        
        $quoteRequestLine = new QuoteRequestLine();
        $quoteRequestLine->setBoite($boite);
        $form = $this->createForm(RequestForBoxType::class, $quoteRequestLine);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $this->panierService->addBoiteRequestToCart($quoteRequestLine);
            $this->addFlash('success', 'Demande dans le panier !');

            //?dans le cas ou il y en aurai plusieurs !!!
            $histories = $request->getSession()->get('history');
            $lastHistory = end($histories);

            $route = $this->generateUrl($lastHistory['route'], array_merge($lastHistory['params'], ['_fragment' => $boite->getId()]));

            return $this->redirect($route);
        
        }

        $metas['description'] = 'Boite de jeu: '.ucfirst(strtolower($boite->getName())).' - '.ucfirst(strtolower($boite->getEditor()->getName())).' - Année '.$yearInDescription;

        return $this->render('site/pages/structures/pieces_detachees_demande.html.twig', [
            'boite' => $boite,
            'metas' => $metas,
            'form' => $form->createView(),
            'search' => $search ?? null,
            'countQuoteRequestLines' => $countQuoteRequestLines,
            'tax' => $this->taxRepository->findOneBy([]),
        ]);
    }

    #[Route('structure-adherente/les-demandes', name: 'structure_adherente_demandes', methods: ['GET', 'POST'] )]
    public function cartForStructureAdherent(Request $request): Response
    {
        $quoteRequest = $this->quoteRequestRepository->findUniqueQuoteRequestWhereStatusIsBeforeSubmission($this->getUser());
        $countQuoteRequestLines = $this->quoteRequestLineRepository->countQuoteRequestLines($this->security->getUser());
        
        if(!$quoteRequest OR count($quoteRequest->getQuoteRequestLines()) == 0){
            $this->addFlash('warning', 'Aucune demande en cours');
            return $this->redirectToRoute('structure_catalogue_pieces_detachees');
        }

        $form = $this->createForm(QuoteRequestChoiceShippingMethodType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $session = $request->getSession();
            $session->set('shippingMethodIdForQuoteRequest', $form->get('shippingMethod')->getData()->getId());
            return $this->redirectToRoute('structure_adherente_demandes_adresses_choices', []);
        }

        return $this->render('site/pages/structures/cart/cart.html.twig', [
            'quoteRequest' => $quoteRequest, 'form' => $form->createView(),
            'quoteRequestLines' => $quoteRequest->getQuoteRequestLines(),
            'countQuoteRequestLines' => $countQuoteRequestLines
        ]);
    }

    #[Route('structure-adherente/les-demandes/choix-des-adresses/', name: 'structure_adherente_demandes_adresses_choices')]
    public function cartForStructureAdherentAdressesChoices(Request $request): Response
    {
        $request->getSession()->get('quoteRequestStep', 'adresses');
        $request->getSession()->set('quoteRequestStep', 'adresses');

        $quoteRequest = $this->quoteRequestRepository->findUniqueQuoteRequestWhereStatusIsBeforeSubmission($this->getUser());
        $countQuoteRequestLines = $this->quoteRequestLineRepository->countQuoteRequestLines($this->security->getUser());

        $shippingMethodIdForQuoteRequest = $request->getSession()->get('shippingMethodIdForQuoteRequest');

        if(!$quoteRequest){
            $this->addFlash('warning', 'Aucune demande en cours');
            return $this->redirectToRoute('structure_catalogue_pieces_detachees');
        }

        $shippingMethod = $this->shippingMethodRepository->findOneBy(['id' => $shippingMethodIdForQuoteRequest]);

         $billingAndDeliveryForm = $this->createForm(BillingAndDeliveryAddressType::class, null, [
            'user' => $this->security->getUser(),
            'shippingMethodId' => $shippingMethodIdForQuoteRequest,
        ]);

        $billingAndDeliveryForm->handleRequest($request);

        if($billingAndDeliveryForm->isSubmitted() && $billingAndDeliveryForm->isValid()){
            $formOk = false;

            $formOk = $this->quoteRequestService->testIfBillingAndDeliveryAdressesAreFromTheUserAndSaveInQuoteRequest($quoteRequest, $billingAndDeliveryForm['billingAddress']->getData()->getId(), $billingAndDeliveryForm['deliveryAddress']->getData()->getId(), $shippingMethodIdForQuoteRequest);


            if($formOk == false){

                //on redirige var la page précèdante
                $this->addFlash('warning', 'Une adresse ou la méthode d\'envoie est inconnue !');
                return $this->redirect($request->headers->get('referer'));

            }else{

                return $this->redirectToRoute('structure_adherente_demandes_recapitulatif');
            }

        }

        return $this->render('site/pages/structures/cart/cartAdresses.html.twig', [
            'quoteRequest' => $quoteRequest, 
            'billingAndDeliveryForm' => $billingAndDeliveryForm->createView(),
            'countQuoteRequestLines' => $countQuoteRequestLines,
            'shippingMethod' => $shippingMethod]);
    }

    #[Route('structure-adherente/les-demandes/recapitulatif-avant-envoi', name: 'structure_adherente_demandes_recapitulatif', methods: ['GET', 'POST'] )]
    public function cartForStructureAdherentRecapitulatif(Request $request): Response
    {
        $quoteRequest = $this->quoteRequestRepository->findUniqueQuoteRequestWhereStatusIsBeforeSubmission($this->getUser());
        $countQuoteRequestLines = $this->quoteRequestLineRepository->countQuoteRequestLines($this->security->getUser());

        //?on supprime le quoteRequestStep de la session
        $request->getSession()->remove('quoteRequestStep');
        
        if(!$quoteRequest){
            $this->addFlash('warning', 'Aucune demande en cours');
            return $this->redirectToRoute('structure_catalogue_pieces_detachees');
        }

        $acceptCartForm = $this->createForm(AcceptCartType::class);
        $acceptCartForm->handleRequest($request);

        if($acceptCartForm->isSubmitted() && $acceptCartForm->isValid()){

            $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
            $quoteRequestStatus = $this->quoteRequestStatusRepository->findOneByLevel(2);
            $quoteRequest->setQuoteRequestStatus($quoteRequestStatus)->setCreatedAt($now);
            $this->em->persist($quoteRequest);
            $this->em->flush();
            return $this->redirectToRoute('structure_adherente_confirmation_envoi', ['quoteRequestId' => $quoteRequest->getId()]);
        }

        return $this->render('site/pages/structures/cart/cart_recap.html.twig', [
            'quoteRequest' => $quoteRequest,
            'acceptCartForm' => $acceptCartForm->createView(),
            'quoteRequestLines' => $quoteRequest->getQuoteRequestLines(),
            'countQuoteRequestLines' => $countQuoteRequestLines
        ]);
    }

    #[Route('structure-adherente/les-demandes/confirmation-envoi/{quoteRequestId}', name: 'structure_adherente_confirmation_envoi')]
    public function cartForStructureAdherentSendConfirmation(Request $request): Response
    {
        $quoteRequest = $this->quoteRequestRepository->findOneBy(['id' => $request->get('quoteRequestId'), 'user' => $this->security->getUser()]);
        $countQuoteRequestLines = $this->quoteRequestLineRepository->countQuoteRequestLines($this->security->getUser());
        
        if(!$quoteRequest){
            $this->addFlash('warning', 'Aucune demande en cours');
            return $this->redirectToRoute('structure_catalogue_pieces_detachees');
        }

        return $this->render('site/pages/structures/cart/cart_confirmation.html.twig', [
            'quoteRequest' => $quoteRequest,
            'countQuoteRequestLines' => $countQuoteRequestLines
        ]);
    }

    #[Route('structure-adherente/les-demandes/{quoteRequestId}/suppression/{quoteRequestLineId}', name: 'structure_adherente_demandes_suppression', requirements: ['id' => '[0-9]+'] )]
    public function cartForStructureAdherentDeleteQrl(int $quoteRequestId, int $quoteRequestLineId): Response
    {
        
        $success = $this->quoteRequestService->deleteQrl($quoteRequestId, $quoteRequestLineId);

        if($success == true){

            $this->addFlash('success', 'Demande supprimée !');
            return $this->redirectToRoute('structure_adherente_demandes');

        }else{

            $this->addFlash('warning', 'Demande introuvable !');
            return $this->redirectToRoute('structure_adherente_demandes');
        }

    }
}
