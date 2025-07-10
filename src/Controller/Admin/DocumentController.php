<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Service\MailService;
use App\Service\PanierService;
use App\Service\DocumentService;
use App\Repository\TaxRepository;
use App\Form\ManualDeliveryPriceType;
use App\Repository\DeliveryRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\QuoteAndDocumentMessageType;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\LegalInformationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormFactoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\DocumentLineType; // Nous devrons créer ce type de formulaire

#[Route('/admin/document', name: 'admin_manual_')]
class DocumentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private DocumentRepository $documentRepository,
        private LegalInformationRepository $legalInformationRepository,
        private TaxRepository $taxRepository,
        private AdminUrlGenerator $adminUrlGenerator,
        private FormFactoryInterface $formFactory,
        private MailService $mailService,
        private DocumentService $documentService,
        private PanierService $panierService,
        // private DocumentService $documentService // Décommenter si un service spécifique est nécessaire pour les documents
    ) {
    }

    #[Route('/{documentId}/details', name: 'document_details')]
    public function details(int $documentId, Request $request): Response
    {
        $document = $this->documentRepository->find($documentId);

        if (!$document) {
            throw $this->createNotFoundException('Document non trouvé.');
        }

        $forms = [];
        foreach($document->getDocumentLines() as $line) {
            $form = $this->formFactory->create(DocumentLineType::class, $line);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->em->persist($line);
                $this->em->flush();
                $this->addFlash('success', 'Ligne du document mise à jour avec succès !');
                // return $this->redirect($request->headers->get('referer'));
            }
            $forms[$line->getId()] = $form->createView();
        }

        // Formulaire pour le prix de livraison manuel
        $manualDeliveryPriceForm = $this->formFactory->create(ManualDeliveryPriceType::class);
        $manualDeliveryPriceForm->handleRequest($request);

        if ($manualDeliveryPriceForm->isSubmitted() && $manualDeliveryPriceForm->isValid()) {
            // Mettre à jour le prix de livraison dans l'entité Document
            $deliveryPrice = $manualDeliveryPriceForm->getData()['price'];
            $document->setDeliveryPriceExcludingTax($deliveryPrice);
            $this->em->persist($document);
            $this->em->flush();
            $this->addFlash('success', 'Prix de livraison mis à jour avec succès !');
        }

        //formulaire pour le message
        $manuelMessageForm = $this->createForm(QuoteAndDocumentMessageType::class, $document);
        $manuelMessageForm->handleRequest($request);

        if ($manuelMessageForm->isSubmitted() && $manuelMessageForm->isValid()) {
            $document->setMessage($manuelMessageForm->get('message')->getData());
            $this->em->persist($document);
            $this->em->flush();
            $this->addFlash('success', 'Message mis à jour avec succès !');
        }

        // Calculs des totaux (similaire à QuoteRequest)
        $totalPriceExcludingTaxOnlyPieces = 0; // Total des lignes de pièces seulement
        $totalWeight = 0;
        foreach ($document->getDocumentLines() as $line) {
             // Supposons que DocumentLine a une méthode getPriceExcludingTax et getQuantity
            $totalPriceExcludingTaxOnlyPieces += $line->getPriceExcludingTax() * $line->getQuantity();
            //TODO if not article ?
            if($line->getItem() != null){
                
                $totalWeight += $line->getItem()->getWeigth() * $line->getQuantity();
            }
        }

        $preparationHt = $document->getCost(); // à définir si applicable pour les documents

        $donnees = $this->documentService->generateValuesForDocument($document);

        if($donnees['items']['totalWeigth'] > 0){
            //on recalcul le cout de la livraison si on a changer une quantité
            $shippingMethodId = $document->getShippingMethod()->getId();
            $deliveryCost = $this->panierService->returnDeliveryCost($shippingMethodId, $totalWeight, $document->getUser());
        }else{
            $deliveryCost = $document->getDeliveryPriceExcludingTax();
        }

        $totalPriceExcludingTax = $totalPriceExcludingTaxOnlyPieces + $preparationHt + $deliveryCost;

        //?on met a jour les totaux du document
        $document->setDeliveryPriceExcludingTax($deliveryCost)->setDeliveryPriceExcludingTax($deliveryCost)->setTotalExcludingTax($totalPriceExcludingTax)->setTotalWithTax($totalPriceExcludingTax + ($totalPriceExcludingTax * $document->getTaxRateValue() / 100));
        $this->em->persist($document);
        $this->em->flush();

        // Vérifiez si le document est déjà envoyé ou finalisé pour désactiver les boutons
        $disabled = ($document->getBillNumber() && $document->getBillNumber() !== null) ? 'disabled' : ''; // Adapter la condition de vérouillage

        return $this->render('admin/quote/manualQuoteDetails.html.twig', [
            'document' => $document,
            'forms' => $forms,
            'manuelMessageForm' => $manuelMessageForm->createView(),
            // 'manualDeliveryPriceForm' => $manualDeliveryPriceForm->createView(),
            'totalPriceExcludingTaxOnlyPieces' => $totalPriceExcludingTaxOnlyPieces,
            'disabled' => $disabled,
            'activeButtonToSendDevisIfAllLinesAreRenseigned' => true, // Logique à adapter si nécessaire
            'manualDeliveryPriceForm' => $manualDeliveryPriceForm->createView(),
        ]);
    }
    
    // Ajoutez ici des actions pour envoyer le document par mail, etc., si nécessaire
    #[Route('/{documentId}/send-mail/{action}', name: 'document_send_mail')]
    public function sendMail(int $documentId, string $action, Request $request): Response
    {
        // Cette logique est à adapter en fonction de votre service de mail pour les documents
        $document = $this->documentRepository->find($documentId);

        if(!$document) {
            $this->addFlash('warning', 'Document inconnu !');
            return $this->redirect($request->headers->get('referer'));
        }else{

            $legales = $this->legalInformationRepository->findOneBy([]);

            $this->mailService->sendMail(
                false,
                $document->getUser()->getEmail(),
                'Votre devis '. $document->getQuoteNumber() . ' est disponible',
                'quoteSendingMessage',
                [
                    'document' => $document,
                    'legales' => $legales
                ],
                null,
                true
            );
                
            $this->addFlash('success', 'Devis envoyé avec succès !');
            return $this->redirect($request->headers->get('referer'));
        }

    }
}