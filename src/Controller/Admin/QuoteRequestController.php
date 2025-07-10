<?php

namespace App\Controller\Admin;

use DateTimeZone;
use DateTimeImmutable;
use App\Service\MailService;
use App\Service\PanierService;
use App\Service\DocumentService;
use App\Service\PaiementService;
use App\Repository\TaxRepository;
use App\Service\UtilitiesService;
use App\Form\QuoteRequestLineType;
use App\Repository\UserRepository;
use App\Repository\BoiteRepository;
use App\Service\QuoteRequestService;
use App\Repository\PaymentRepository;
use App\Repository\ReserveRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SiteSettingRepository;
use App\Repository\QuoteRequestRepository;
use App\Repository\DocumentStatusRepository;
use App\Repository\ShippingMethodRepository;
use App\Repository\MeansOfPayementRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\LegalInformationRepository;
use App\Repository\QuoteRequestLineRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\DocumentParametreRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\QuoteRequestStatusRepository;
use App\Form\ManualDeliveryPriceType;
use App\Form\QuoteAndDocumentMessageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class QuoteRequestController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentStatusRepository $documentStatusRepository,
        private DocumentService $documentService,
        private EntityManagerInterface $em,
        private PaymentRepository $paymentRepository,
        private LegalInformationRepository $legalInformationRepository,
        private MailService $mailService,
        private PaiementService $paiementService,
        private SiteSettingRepository $siteSettingRepository,
        private UserRepository $userRepository,
        private ReserveRepository $reserveRepository,
        private BoiteRepository $boiteRepository,
        private ShippingMethodRepository $shippingMethodRepository,
        private PanierService $panierService,
        private TaxRepository $taxRepository,
        private UtilitiesService $utilitiesService,
        private AdminUrlGenerator $adminUrlGenerator,
        private DocumentParametreRepository $documentParametreRepository,
        private MeansOfPayementRepository $meansOfPayementRepository,
        private QuoteRequestRepository $quoteRequestRepository,
        private QuoteRequestLineRepository $quoteRequestLineRepository,
        private QuoteRequestService $quoteRequestService,
        private QuoteRequestStatusRepository $quoteRequestStatusRepository
    )
    {
    }

    #[Route('/admin/traitement-demande-de-devis/{quoteRequestId}', name: 'admin_manual_quote_request_details')]
    public function adminQuoteRequestDetails(int $quoteRequestId, Request $request): Response
    {
        $quoteRequest = $this->quoteRequestRepository->findOneById($quoteRequestId);

        if(!$quoteRequest){
            $this->addFlash('warning', 'Demande de devis inconnue !');
            return $this->redirectToRoute('admin');
        }else{

            $manualDeliveryPriceForm = $this->createForm(ManualDeliveryPriceType::class);
            $manualDeliveryPriceForm->handleRequest($request);

            $manuelMessageForm = $this->createForm(QuoteAndDocumentMessageType::class, $quoteRequest);
            $manuelMessageForm->handleRequest($request);

            if($manuelMessageForm->isSubmitted() && $manuelMessageForm->isValid()) {
                $quoteRequest->setMessage($manuelMessageForm->get('message')->getData());
                $this->em->persist($quoteRequest);
                $this->em->flush();
                $this->addFlash('success', 'Message mis à jour avec succès !');
            }

            $forms = []; // Tableau pour stocker les formulaires de chaque ligne
            $totalPriceExcludingTax = 0;
            $totalWeight = 0;
            $totalPriceExcludingTaxOnlyPieces = 0;
            $tax = $this->taxRepository->findOneBy([]);

            $lineIdsInQuoteRequest = [];
            $activeButtonToSendDevisIfAllLinesAreRenseigned = false;
            $countLinesAreRenseigned = 0;
            $quoteRequestLines = $quoteRequest->getQuoteRequestLines();

            foreach($quoteRequest->getQuoteRequestLines() as $line) {
    
                $lineIdsInQuoteRequest[] = $line->getId();
                $form = $this->createForm(QuoteRequestLineType::class, $line, [
                    'action' => $this->generateUrl('admin_manual_quote_request_details_update_line', ['quoteRequestId' => $quoteRequest->getId(), 'lineId' => $line->getId()]),
                    'method' => 'POST',
                    'attr' => [
                        'id' => 'form_quote_request_line_' . $line->getId(),
                    ]
                ]);


                $forms[$line->getId()] = $form->createView();

                // Calculate totals for display, irrespective of submission
                $totalPriceExcludingTaxOnlyPieces += $line->getPriceExcludingTax();
                $totalWeight += $line->getWeight();
                if($line->getPriceExcludingTax() !== null){
                    $countLinesAreRenseigned += 1;
                }
            }
            if($countLinesAreRenseigned == count($quoteRequestLines)){
                $activeButtonToSendDevisIfAllLinesAreRenseigned = true;
            }

            // Calculate delivery cost and total price based on current state of all lines
            if($manualDeliveryPriceForm->isSubmitted() && $manualDeliveryPriceForm->isValid()) {

                $deliveryCost = $manualDeliveryPriceForm->getData()['price'];
                $request->getSession()->set('deliveryCost', $deliveryCost);

            }else{

                if($request->getSession()->has('deliveryCost')) {

                    $deliveryCost = $request->getSession()->get('deliveryCost');

                }else{
                    
                    $deliveryCost = $this->panierService->returnDeliveryCost($quoteRequest->getShippingMethod(), $totalWeight, $quoteRequest->getUser());
                }
            }
            
            //pas de préparation pour les structures adhérente donc 0
            // $preparationHt = $this->documentParametreRepository->findOneBy([])->getPreparation();
            $preparationHt = 0;

            $totalPriceExcludingTax = $totalPriceExcludingTaxOnlyPieces + $deliveryCost + $preparationHt;

            return $this->render('admin/quoteRequest/manualQuoteRequestDetails.html.twig', [
                'quoteRequest' => $quoteRequest,
                'forms' => $forms,
                'deliveryCost' => $deliveryCost,
                'totalPriceExcludingTax' => $totalPriceExcludingTax,
                'tax' => $tax,
                'activeButtonToSendDevisIfAllLinesAreRenseigned' => $activeButtonToSendDevisIfAllLinesAreRenseigned,
                'preparationHt' => $preparationHt,
                'totalWeight' => $totalWeight,
                'manuelMessageForm' => $manuelMessageForm->createView(),
                'totalPriceExcludingTaxOnlyPieces' => $totalPriceExcludingTaxOnlyPieces,
                'manualDeliveryPriceForm' => $manualDeliveryPriceForm->createView(),
            ]);
        }

    }

    #[Route('admin/traitement-demande-de-devis/{quoteRequestId}/update-line/{lineId}', name: 'admin_manual_quote_request_details_update_line', methods: ['POST'])]
    public function updateLine(Request $request, int $quoteRequestId, int $lineId): Response
    {
        $line = $this->quoteRequestLineRepository->findOneBy(['id' => $lineId, 'quoteRequest' => $quoteRequestId]);

        if(!$line){
            $this->addFlash('warning', 'Ligne de demande de devis introuvable !');
            return $this->redirectToRoute('admin');
        }else{

            $form = $this->createForm(QuoteRequestLineType::class, $line);
            $form->handleRequest($request);
          
            
            if($form->isSubmitted() && $form->isValid()) {
                $this->em->persist($line);
                $this->em->flush();
                $this->addFlash('success', 'Ligne mise à jour avec succès !');
            } else {
                // Add flash messages for form errors if needed
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }

            return $this->redirect($request->headers->get('referer').'#form_quote_request_line_'.$lineId+1);
        }
    }


    #[Route('admin/traitement-demande-de-devis/sendMail/{quoteRequestId}/{action}', name: 'admin_manual_quote_request_send_mail', methods: ['GET'])]
    public function quoteRequestSendMail(Request $request, int $quoteRequestId, string $action, AdminUrlGenerator $adminUrlGenerator): Response
    {

        $quoteRequest = $this->quoteRequestRepository->findOneById($quoteRequestId);
        $actions = ['quoteRequestSendMailToCustomerWithPrices', 'quoteRequestSendMailToCustomerWithoutPrices'];

        if(!$quoteRequest || !in_array($action, $actions)){
            $this->addFlash('warning', 'Demande de devis ou action inconnue !');
            return $this->redirect($request->headers->get('referer'));
        }else{

            $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
            $legales = $this->legalInformationRepository->findOneBy([]);

            if($action == 'quoteRequestSendMailToCustomerWithPrices' && $quoteRequest->getDocument() == null){
                $donnees = $this->quoteRequestService->setDetailsPanierForDocumentGeneration($quoteRequest);
                $this->quoteRequestService->setDonneesInSessionForDocumentGeneration($quoteRequest, $request);
                $document = $this->documentService->saveDocumentLogicInDataBase($donnees, $request->getSession(), $request, $quoteRequest);
                $this->documentService->generateAllLinesFromPanierIntoDocumentLines($quoteRequest->getQuoteRequestLines()->toArray(), $document);
                $quoteRequest->setDocument($document);
            }

            $quoteRequest->setIsSendByEmail(true)->setSendByEmailAt($now)->setQuoteRequestStatus($this->quoteRequestStatusRepository->findOneBy(['level' => 3]));
            $this->em->persist($quoteRequest);
            $this->em->flush();

            $this->mailService->sendMail(
                false,
                $quoteRequest->getUser()->getEmail(),
                'Votre demande de devis du '.$quoteRequest->getCreatedAt()->format('d/m/Y').' - '.$quoteRequest->getDocument()->getQuoteNumber(),
                $action,
                [
                    'quoteRequest' => $quoteRequest,
                    'legales' => $legales
                ],
                null,
                true
            );
            
            //?on detruit la valeur du cout de la livraison
            $request->getSession()->remove('deliveryCost');

            $url = $adminUrlGenerator
                ->setController(\App\Controller\Admin\EasyAdmin\QuoteRequestCrudController::class)
                ->setAction(Action::INDEX) // Ou Crud::PAGE_INDEX
                ->generateUrl();

            $this->addFlash('success', 'Devis envoyé avec succès !');
            return $this->redirect($url);
        }
    }
}
