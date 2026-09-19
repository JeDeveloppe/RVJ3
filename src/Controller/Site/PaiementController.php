<?php

namespace App\Controller\Site;

use App\Repository\DocumentRepository;
use Exception;
use App\Service\PaiementService;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
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

            }else if(isset($response['redirectUrl'])){

                //?HelloAsso n'a encore rien enregistre : on renvoie le client sur sa page de paiement (URL externe, pas un nom de route)
                return $this->redirect($response['redirectUrl']);

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

    //?HelloAsso notifie une URL FIXE configuree dans son back-office : il n'y a pas de token dans l'adresse.
    //?La notification ("Payment" / state "Authorized") ne contient ni l'identifiant du checkout ni nos metadonnees
    //?(cf. https://dev.helloasso.com/docs/paiement-autorisé-sur-un-checkout) : on s'en sert uniquement comme
    //?declencheur, et on reverifie les paiements en attente aupres de l'API HelloAsso (on ne fait jamais confiance au contenu recu).
    #[Route('/paiement/notificationUrl/', name: 'paiement_notificationUrl_helloasso', methods: ['POST'])]
    public function notificationUrlHelloAsso(Request $request): Response
    {
        if($_ENV["PAIEMENT_MODULE"] != "HELLOASSO"){
            throw $this->createNotFoundException();
        }

        $payload = json_decode($request->getContent(), true);
        $this->traceHelloAssoNotification($payload);

        //seuls les paiements autorises nous interessent (HelloAsso envoie aussi des evenements "Order", remboursements...)
        if(!is_array($payload) || ($payload['eventType'] ?? null) !== 'Payment' || ($payload['data']['state'] ?? null) !== 'Authorized'){
            return new Response('OK');
        }

        //si l'API HelloAsso est injoignable on repond une erreur pour qu'HelloAsso renvoie la notification plus tard
        if(!$this->paiementService->verifyHelloAssoPayments()){
            return new Response('Erreur temporaire', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new Response('OK');
    }

    //?Trace minimale (sans donnee personnelle) des notifications recues, pour pouvoir verifier qu'elles arrivent.
    //?Fichier dedie car le log prod (php://stderr) n'est pas consultable sur l'hebergement. Taille plafonnee.
    private function traceHelloAssoNotification(mixed $payload): void
    {
        try {
            $file = $this->getParameter('kernel.logs_dir').'/helloasso_notifications.log';

            $line = sprintf(
                "%s eventType=%s state=%s paymentId=%s orderId=%s amount=%s\n",
                date('Y-m-d H:i:s'),
                is_array($payload) ? json_encode($payload['eventType'] ?? null) : 'non-json',
                is_array($payload) ? json_encode($payload['data']['state'] ?? null) : '-',
                is_array($payload) ? json_encode($payload['data']['id'] ?? null) : '-',
                is_array($payload) ? json_encode($payload['data']['order']['id'] ?? null) : '-',
                is_array($payload) ? json_encode($payload['data']['amount'] ?? null) : '-'
            );

            //on repart de zero au-dela de 200 Ko
            $flags = (is_file($file) && filesize($file) > 200000) ? LOCK_EX : FILE_APPEND | LOCK_EX;
            file_put_contents($file, $line, $flags);
        } catch (\Throwable $e) {
            //la trace ne doit jamais empecher de traiter la notification
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