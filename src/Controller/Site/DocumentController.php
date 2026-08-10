<?php

namespace App\Controller\Site;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Form\AcceptCartType;
use App\Service\DocumentService;
use App\Repository\DocumentRepository;
use App\Repository\LegalInformationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DocumentController extends AbstractController
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentService $documentService,
        private LegalInformationRepository $legalInformationRepository
    )
    {
    }

    #[Route('/document/{tokenDocument}', name: 'document_view')]
    public function lectureDevis(
        $tokenDocument,
        Request $request
        ): Response
    {

        $document = $this->documentRepository->findOneBy(['token' => $tokenDocument]);

        if(!$document){

            return $this->documentService->renderIfDocumentNoExist();

        }else{

            $acceptCartForm = $this->createForm(AcceptCartType::class);
            $acceptCartForm->handleRequest($request);

            if($acceptCartForm->isSubmitted() && $acceptCartForm->isValid())
            {
                return $this->redirectToRoute('paiement', ['tokenDocument' => $document->getToken()]);
            }

            $donnees = $this->documentService->generateValuesForDocument($document);

            return $this->render('site/document_view/_document_view.html.twig', [
                'document' => $document,
                'acceptCartForm' => $acceptCartForm,
                'donnees' => $donnees,
            ]);
        }
    }

    #[Route('/document/impression/{tokenDocument}', name: 'quote_print')]
    public function qrPrint(string $tokenDocument): Response // Le type de retour est StreamedResponse
    {
        $document = $this->documentRepository->findOneBy(['token' => $tokenDocument]);

        if (!$document) {
            $this->addFlash('danger', 'La demande de devis n\'existe pas...');
            return $this->redirectToRoute('app_home'); // Redirection si le document n'existe pas
        }

        $legales = $this->legalInformationRepository->findOneBy(['isOnline' => true], ['id' => 'ASC']);
        $donnees = $this->documentService->generateValuesForDocument($document);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultPaperSize', 'A4');
        $options->set('defaultPaperOrientation', 'portrait');


        $dompdf = new Dompdf($options);

        // Récupérez le HTML de votre template Twig
        $html = $this->renderView('site/document_view/print/print.html.twig', [
            'document' => $document,
            'legales' => $legales,
            'donnees' => $donnees
        ]);

        $dompdf->loadHtml($html);

        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$document->getQuoteNumber().'.pdf"',
        ]);
    }

    #[Route('/download/facture/{tokenDocument}', name: 'download_billing_document')]
    public function factureDownload($tokenDocument): Response
    {

        $document = $this->documentRepository->findOneBy(['token' => $tokenDocument]);

        if(!$document){

            return $this->documentService->renderIfDocumentNoExist();

        }else{

            $dompdfInstance = $this->documentService->generatePdf($document);

               $filename = "Facture RVJ - " . $document->getBillNumber() . ".pdf";

            return new Response($dompdfInstance->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"', // 'inline' pour afficher directement
            ]);
        }
    }
}
