<?php

namespace App\Controller\Site;

use App\Repository\DocumentRepository;
use Exception;
use App\Service\PaiementService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;


class PaiementController extends AbstractController
{

    public function __construct(
        private PaiementService $paiementService,
        private Security $security,
        private DocumentRepository $documentRepository
    ){  
    }

    #[Route('/paiement/{tokenDocument}', name: 'paiement')]
    public function creationPaiement($tokenDocument)
    {
        if($_ENV["PAIEMENT_MODULE"] == "STRIPE")
        {
            $session = $this->paiementService->creationPaiementWithStripe($tokenDocument);
            return $this->redirect($session->url, 303);

        }else if($_ENV["PAIEMENT_MODULE"] == "PAYPLUG")
        {
            $payment_url = $this->paiementService->creationPaiementWithPayplug($tokenDocument);
            return $this->redirect($payment_url, 303);

        }else if($_ENV["PAIEMENT_MODULE"] == "HELLOASSO")
        {
            try {
                $payment_url = $this->paiementService->creationPaiementWithHelloAsso($tokenDocument);
                return $this->redirect($payment_url, 303);
            } catch (\Exception $e) {
                $this->addFlash('warning', $e->getMessage());
                return $this->redirectToRoute('paiement_canceled', ['tokenDocument' => $tokenDocument]);
            }

        }else{

            throw new Exception('PAIEMENT_MODULE IN .ENV FILE NOT INFORM');
            
        }
    }

    #[Route('/paiement/validation/{tokenDocument}', name: 'paiement_success')]
    public function paiementSuccess($tokenDocument)
    {
        if($_ENV["PAIEMENT_MODULE"] == "STRIPE")
        {
            $this->paiementService->paiementSuccessWithStripe($tokenDocument);

        }else if($_ENV["PAIEMENT_MODULE"] == "PAYPLUG")
        {
            $response = $this->paiementService->paiementSuccessWithPayplug($tokenDocument);

            //si on a bien vérifié le paiement
            if(array_key_exists('paiement', $response)){
                return $this->render('site/pages/paiement/success.html.twig', [
                    'token' => $tokenDocument,
                ]);
            }else{
                $this->addFlash('warning', $response['messageFlash']);
                return $this->redirectToRoute($response['route']);
            }


        }else if($_ENV["PAIEMENT_MODULE"] == "HELLOASSO"){

            $response = $this->paiementService->paiementSuccessWithHelloAsso($tokenDocument);

            //si on a bien vérifié le paiement
            if($response['paiement'] == true)
            {
                return $this->render('site/pages/paiement/success.html.twig', [
                    'token' => $tokenDocument,
                ]);

            }else{

                return $this->redirectToRoute($response['route']);

            }
        
        }else{

            throw new Exception('PAIEMENT_MODULE IN .ENV FILE NOT INFORM');
        }
    }

    #[Route('/paiement/annulation-achat/{tokenDocument}', name: 'paiement_canceled')]
    public function paiementCancel($tokenDocument)
    {
        $document = $this->documentRepository->findOneBy(['token' => $tokenDocument]);

        if(!$document){
            //pas de devis
            $this->addFlash('warning', 'Document inconnu!');
            return $this->redirectToRoute('app_home');

        }else{

            return $this->render('site/pages/paiement/cancel.html.twig', [
                'token' => $tokenDocument,
            ]);
        }
    }

    #[Route('/paiement/notificationUrl/{tokenDocument}', name: 'paiement_notificationUrl')]
    public function notificationUrl($tokenDocument)
    {
        if($_ENV["PAIEMENT_MODULE"] == "STRIPE")
        {
            $this->paiementService->notificationUrlWithStripe($tokenDocument);

        }else if($_ENV["PAIEMENT_MODULE"] == "PAYPLUG")
        {
            $this->paiementService->notificationUrlWithPayplug($tokenDocument);

        }else if($_ENV["PAIEMENT_MODULE"] == "HELLOASSO")
        {
            $this->paiementService->notificationUrlWithHelloAsso($tokenDocument);

        }else{
            throw new Exception('PAIEMENT_MODULE IN .ENV FILE NOT INFORM');
        }

        return new Response();
    }
}