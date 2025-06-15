<?php

namespace App\Controller\Admin;

use App\Controller\Admin\EasyAdmin\DocumentCrudController;
use App\Entity\Panier;
use App\Entity\QuoteRequestLine;
use DateTimeImmutable;
use App\Service\MailService;
use App\Service\PanierService;
use App\Service\DocumentService;
use App\Service\PaiementService;
use App\Repository\TaxRepository;
use App\Service\UtilitiesService;
use App\Repository\UserRepository;
use App\Repository\BoiteRepository;
use App\Repository\PaymentRepository;
use App\Repository\ReserveRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\DetailsForManualInvoiceType;
use App\Repository\SiteSettingRepository;
use App\Repository\DocumentStatusRepository;
use App\Repository\ShippingMethodRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\LegalInformationRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\DocumentParametreRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\BillingAndDeliveryAddressForManualInvoiceType;
use App\Form\QuoteRequestLineType;
use App\Repository\MeansOfPayementRepository;
use App\Repository\QuoteRequestLineRepository;
use App\Repository\QuoteRequestRepository;
use App\Service\DeliveryService;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
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
        private QuoteRequestLineRepository $quoteRequestLineRepository
    )
    {
    }

    #[Route('/admin/change-status-of-document/{document}/{status}', name: 'admin_change_status_document')]
    public function changeStatusDocument($document,$status): Response
    {
        $document = $this->documentRepository->findOneBy(['token' => $document]);
        $status = $this->documentStatusRepository->findOneby(['action' => $status]);

        if(!$document || !$status){

            $this->addFlash('warning', 'Document inconnu !');
            return $this->redirectToRoute('admin_traited_daily_commands');

        }else{

            $this->documentService->statusChange($document,$status);

            $this->addFlash('success', 'Status mis à jour !');
            return $this->redirectToRoute('admin_traited_daily_commands');

        }
    }

    #[Route('/admin/change-status-of-option/{option}/{value}', name: 'admin_change_status_option')]
    public function changeStatusOption($option,$value, Request $request): Response
    {

        $setting = $this->siteSettingRepository->findOneBy([]);

        switch ($option) {
            case 'setBlockEmailSending':
                $this->addFlash('success', 'Status mis à jour !');
                $setting->setBlockEmailSending($value);
                break;
            default:
                $this->addFlash('danger', 'Méthode inconnue dans le switch !');
        }

        $this->em->persist($setting);
        $this->em->flush();

        return $this->redirect($request->headers->get('referer'));

    }

    #[Route('/admin/verification-achats-helloAsso', name: 'admin_verification_achats_helloAsso')]
    public function verificationAchatsHelloAsso(Request $request)
    {

        //on cherche tous les documents crées depuis le 10-11-2024
        $datetimeImmutable = new DateTimeImmutable();
        $date = $datetimeImmutable->setDate(2024, 11, 10);

        $documents = $this->documentRepository->findDocumentsCreatedAfterDateAndNotBilled($date);
        
        foreach($documents as $document){

            $this->paiementService->updateDocumentAndPaiementWithHelloAssoStatus($document);

        }

        return new Response('TERMINER: tous les paiements sur HelloAsso ont été mis à jour ! (100% de réussite)');

    }


    #[Route('/admin/creation-facture-manuelle/details/{reserveId}', name: 'admin_manual_invoice_details')]
    public function manualInvoiceDetails(Request $request, $reserveId): Response
    {
        $reserve = $this->reserveRepository->findOneById($reserveId);
        $countAdressFromUser = 0;
        $countAdressFromUser = count($reserve->getUser()->getAddresses());

        if(!$reserve){

            $this->addFlash('warning', 'Réservation inconnue !');
            return $this->redirectToRoute('admin');

        }else{

            $detailsForManualInvoiceForm = $this->createForm(DetailsForManualInvoiceType::class);
            $detailsForManualInvoiceForm->handleRequest($request);

            if($detailsForManualInvoiceForm->isSubmitted() && $detailsForManualInvoiceForm->isValid()){

                $details = [
                    'paiementId' => $detailsForManualInvoiceForm['paiement']->getData()->getId(),
                    'shippingMethodId' => $detailsForManualInvoiceForm['shippingMethod']->getData()->getId(),
                    'reserveId' => $reserve->getId(),
                    'transactionDate' => $detailsForManualInvoiceForm['transactionDate']->getData()->format('Y-m-d'),
                ];

                //?on met en session pour passer sur la page suivante
                $request->getSession()->set('detailsForManualInvoice', $details);

                //?on redirige vers la page suivante
                $url = $this->adminUrlGenerator->setRoute('admin_manual_invoice_prices_address', ['reserveId' => $reserve->getId()])->generateUrl();
                return $this->redirect($url);

            }

            return $this->render('admin/manual_invoice/setDetails.html.twig', [
                'reserve' => $reserve,
                'detailsForManualInvoice' => $detailsForManualInvoiceForm,
                'countAdressFromUser' => $countAdressFromUser
            ]);
        }
    }

    #[Route('/admin/creation-facture-manuelle/prix-adresses/{reserveId}', name: 'admin_manual_invoice_prices_address')]
    public function manualInvoicePricesAndAddress(Request $request, $reserveId): Response
    {
        $reserve = $this->reserveRepository->findOneById($reserveId);
        $detailsForManualInvoice = $request->getSession()->get('detailsForManualInvoice');


        if(!$reserve or !$detailsForManualInvoice){

            $this->addFlash('warning', 'Réservation ou détails inconnue !');
            return $this->redirectToRoute('admin');

        }else{

            $shippingMethod = $this->shippingMethodRepository->findOneById($detailsForManualInvoice['shippingMethodId']);
            $meanOfPaiement = $this->meansOfPayementRepository->findOneById($detailsForManualInvoice['paiementId']);
            $transactionDate = new DateTimeImmutable($detailsForManualInvoice['transactionDate'], new \DateTimeZone('Europe/Paris'));


            $billingAndDeliveryForm = $this->createForm(BillingAndDeliveryAddressForManualInvoiceType::class, null,[
                'user' => $reserve->getUser(),
                'shippingMethodId' => $shippingMethod->getId(),
            ]);
            $billingAndDeliveryForm->handleRequest($request);

            //on genere des paniers pour chaque occasions
            $paniers = [];
            foreach($reserve->getOccasions() as $occasion){
                $panier = new Panier();
                $panier->setOccasion($occasion);
                $panier->setQte(1);
                $panier->setPriceWithoutTax($occasion->getPriceWithoutTax() * 100);
                $panier->setUnitPriceExclusingTax($occasion->getPriceWithoutTax() * 100);
                $panier->setUser($reserve->getUser());
                $panier->setCreatedAt(new DateTimeImmutable('now'));
                $this->em->persist($panier);
                $paniers[] = $panier;
            }
            //on calcule le poidt total du panier
            $reponses['totauxOccasions'] = $this->utilitiesService->totauxByPanierGroup($paniers);
            //frais de gestion
            $cost = 0;
            if($reponses['totauxOccasions']['price'] == 0){
                $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);
                $cost = $docParams->getPreparation();
            }



            if($billingAndDeliveryForm->isSubmitted() && $billingAndDeliveryForm->isValid()){

                $allValuesInRequest = $request->request->all();
                $allPricesHtFromRequest = $allValuesInRequest['billingPricesHt'];
                $billingAddressFromForm = $billingAndDeliveryForm['billingAddress']->getData();
                $deliveryAddressFromForm = $billingAndDeliveryForm['deliveryAddress']->getData();
                $shippingMethod = $this->shippingMethodRepository->find($detailsForManualInvoice['shippingMethodId']);

                //on creer le document, on supprime reserve et on redirige vers la page suivante
                $document = $this->documentService->generateDocumentInDatabaseFromReserve($reserve, $allPricesHtFromRequest, $billingAddressFromForm, $deliveryAddressFromForm, $shippingMethod);

                //puis on redirige vers les reservations zvec un message d'action
                $this->addFlash('success', 'Facture manuelle crée avec successe ! '.$document->getBillNumber());
                $url = $this->adminUrlGenerator->setController(DocumentCrudController::class)->setAction('index')->generateUrl();
                return $this->redirect($url);

            }

            return $this->render('admin/manual_invoice/setPricesAndAddress.html.twig', [
                'reserve' => $reserve,
                'billingAndDeliveryForm' => $billingAndDeliveryForm,
                'shippingMethod' => $shippingMethod,
                'meanOfPaiement' => $meanOfPaiement,
                'transactionDate' => $transactionDate,
                'shippingMethodIdForJavascript' => $shippingMethod->getId(),
                'cartWeightForJavascript' => $reponses['totauxOccasions']['weigth'],
                'costForJavascript' => $cost,
            ]);
        }
    }
}
