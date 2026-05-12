<?php

namespace App\Service;

use Exception;
use Stripe\Stripe;
use DateTimeImmutable;
use League\Csv\Reader;
use App\Entity\Payment;
use App\Entity\Document;
use App\Repository\PaymentRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Response;
use App\Repository\DocumentStatusRepository;
use App\Repository\MeansOfPayementRepository;
use Symfony\Component\Routing\RouterInterface;
use App\Repository\DocumentParametreRepository;
use App\Repository\LegalInformationRepository;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaiementService
{

    public function __construct(
        private EntityManagerInterface $em,
        private UtilitiesService $utilities,
        private DocumentService $documentService,
        private DocumentRepository $documentRepository,
        private DocumentParametreRepository $documentParametreRepository,
        private UrlGeneratorInterface $urlGeneratorInterface,
        private RouterInterface $router,
        private MeansOfPayementRepository $meansOfPayementRepository,
        private PaymentRepository $paymentRepository,
        private DocumentStatusRepository $documentStatusRepository,
        private HttpClientInterface $client,
        private UtilitiesService $utilitiesService,
        private RequestStack $requestStack,
        private MailService $mailService,
        private LegalInformationRepository $legalInformationRepository
        ){
    }

    public function creationPaiementWithStripe($token): Response
    {

        $document = $this->checkIfDocumentExistInDatabase($token);

        //on s'identifie
        $this->stripeAuth();

        $session = Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    
                    'product_data' => [
                            'name' => 'Devis '.$document->getNumeroDevis(),
                        ],
                    'unit_amount' => $document->getTotalTTC(),
                ],
              'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->router->generateUrl('paiement_success', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->router->generateUrl('paiement_canceled', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
          ]);

        // //on renseigne le paiement
        // $paiement = new Paiement();
        // $paiement->setDocument($document)
        //         ->setTokenTransaction($payment_id)
        //         ->setCreatedAt(new DateTimeImmutable('now'));
        // //on sauvegarde le paiement
        // $this->em->persist($paiement);
        // $this->em->flush();

        // //on met a jour le document lui meme
        // $document->setPaiement($paiement);
        // $this->em->merge($document);
        // $this->em->flush();

        return $session;
    }

    public function creationPaiementWithPayplug($token)
    {


        $document = $this->checkIfDocumentExistInDatabase($token);

        //on s'identifie
        $this->payplugAuth();

        $customer_id = $document->getToken();
        
        $arrayAdresseF = $this->explodeAdresse($document->getBillingAddress(),$document);
        $arrayAdresseL = $this->explodeAdresse($document->getDeliveryAddress(),$document);

        $payment = \Payplug\Payment::create([
                'amount'            => $document->getTotalWithTax(),
                'currency'          => 'EUR',
                'billing'          => [
                    'title'        => $arrayAdresseF['title'],
                    'first_name'   => $arrayAdresseF['first_name'],
                    'last_name'    => $arrayAdresseF['last_name'],
                    'email'        => $arrayAdresseF['email'],
                    'address1'     => $arrayAdresseF['adresse1'],
                    'postcode'     => $arrayAdresseF['postCode'],
                    'city'         => $arrayAdresseF['city'],
                    'country'      => $arrayAdresseF['country'],
                    'language'     => $arrayAdresseF['language']
                ],
                'shipping'          => [
                    'title'        => $arrayAdresseL['title'],
                    'first_name'   => $arrayAdresseL['first_name'],
                    'last_name'    => $arrayAdresseL['last_name'],
                    'email'        => $arrayAdresseL['email'],
                    'address1'     => $arrayAdresseL['adresse1'],
                    'postcode'     => $arrayAdresseL['postCode'],
                    'city'         => $arrayAdresseL['city'],
                    'country'      => $arrayAdresseL['country'],
                    'language'     => $arrayAdresseL['language'],
                    'delivery_type' => 'BILLING'
                ],
                'hosted_payment' => [
                    'return_url' => $this->urlGeneratorInterface->generate('paiement_success', ['tokenDocument' => $customer_id], UrlGeneratorInterface::ABSOLUTE_URL),
                    'cancel_url' => $this->urlGeneratorInterface->generate('paiement_canceled', ['tokenDocument' => $customer_id], UrlGeneratorInterface::ABSOLUTE_URL)
                ],
                'notification_url' => $this->urlGeneratorInterface->generate('paiement_notificationUrl', ['tokenDocument' => $customer_id], UrlGeneratorInterface::ABSOLUTE_URL),
                'metadata'         => [
                    'customer_id'  => $customer_id
                ]
        ]);

        $payment_url = $payment->hosted_payment->payment_url;
        $payment_id = $payment->id;



        $paiement = $this->paymentRepository->findOneBy(['document' => $document]);

        if(!$paiement){
            $paiement = new Payment();
        }

        //on renseigne le paiement
        $paiement->setDocument($document)
                ->setMeansOfPayment($this->meansOfPayementRepository->findOneBy(['name' => 'CB']))
                ->setTokenPayment($payment_id)
                ->setCreatedAt(new DateTimeImmutable('now'));
        //on sauvegarde le paiement
        $this->em->persist($paiement);
        $this->em->flush();

        return $payment_url;
    }

    
    public function creationPaiementWithHelloAsso($token)
    {

        $document = $this->checkIfDocumentExistInDatabase($token);

        //on s'identifie
        $bearer = $this->helloAssoAuth();

        $customer_id = $document->getToken();
        
        $arrayAdresseF = $this->explodeAdresse($document->getBillingAddress(),$document);
        $arrayAdresseL = $this->explodeAdresse($document->getDeliveryAddress(),$document);

        $countryIsoCode3 = $this->transformIsoCode2ToIsoCode3($arrayAdresseF['country']);

        $body = 
            [
            "totalAmount" => $document->getTotalWithTax(),
            "initialAmount" => $document->getTotalWithTax(),
            "itemName" => "Achat sur Refaites vos jeux",
            "backUrl" => $this->urlGeneratorInterface->generate('paiement_canceled', ['tokenDocument' => $customer_id], UrlGeneratorInterface::ABSOLUTE_URL),
            "errorUrl" => $this->urlGeneratorInterface->generate('paiement_canceled', ['tokenDocument' => $customer_id], UrlGeneratorInterface::ABSOLUTE_URL),
            "returnUrl" => $this->urlGeneratorInterface->generate('paiement_success', ['tokenDocument' => $customer_id], UrlGeneratorInterface::ABSOLUTE_URL),
            "containsDonation" => false,
            "payer" => [
                "firstName" => $arrayAdresseF['first_name'],
                "lastName" => $arrayAdresseF['last_name'],
                "email" => $arrayAdresseF['email'],
                "dateOfBirth" => "",
                "address" => $arrayAdresseF['adresse1'],
                "city" => $arrayAdresseF['city'],
                "zipCode" => $arrayAdresseF['postCode'],
                "country" => $countryIsoCode3,
                "companyName" => $arrayAdresseF['title']
            ],
            "metadata" =>  [
                "reference" => $document->getQuoteNumber(),
                "libelle" => "Achat sur Refaites vos jeux",
                "userId" => $document->getUser()->getId(),
                ]
            ];




        try {
            $result = $this->client->request('POST', $_ENV['HELLO_ASSO_URL_API'],
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$bearer
                ],
                'body' => json_encode($body),
            ]);

            $content = $result->toArray();
        } catch (\Exception $e) {
            // Log l'erreur pour debug
            error_log('HelloAsso API error: ' . $e->getMessage() . ' for country: ' . $countryIsoCode3 . ' body: ' . json_encode($body));
            // Message spécifique pour la Belgique
            if ($countryIsoCode3 === 'BEL') {
                throw new \Exception('Le paiement depuis la Belgique n\'est actuellement pas disponible avec HelloAsso. Veuillez contacter le support.');
            } else {
                throw new \Exception('Erreur lors de la création du paiement avec HelloAsso. Veuillez réessayer ou contacter le support.');
            }
        }


        $paiement = $this->paymentRepository->findOneBy(['document' => $document]);

        if(!$paiement){
            $paiement = new Payment();
        }

        //dans tous les cas une entity paiement est creee
        $paiement->setDocument($document)
                // ->setMeansOfPayment($this->meansOfPayementRepository->findOneBy(['name' => 'CB']))
                ->setMeansOfPayment(null)
                ->setTokenPayment($content['id'])
                ->setCreatedAt(new DateTimeImmutable('now'));
        //on sauvegarde le paiement
        $this->em->persist($paiement);
        $this->em->flush();


        return $content['redirectUrl'];
    }

    public function paiementSuccessWithStripe($token)
    {
        throw new Exception('function paiementSuccessWithStripe in paiementService NOT INFORM');
    }
    
    public function paiementSuccessWithPayplug($token)
    {
        $response = [];

        $document = $this->documentRepository->findOneBy(['token' => $token]);
        $docParams = $this->documentParametreRepository->findOneBy([]);

        if(!$document){
            //pas de devis
            $response['messageFlash'] = 'Document inconnu!';
            $response['route'] = 'app_home';

            return $response;

        }else if(!empty($document->getBillNumber())){
            //document deja facturé
            $response['paiement'] = 'Document déjà payé!';

            return $response;

        }else if(is_null($document->getBillNumber()) OR empty($document->getBillNumber())){

            //on s'identifie
            $this->payplugAuth();
            //on interroge le paiement
            $payment = \Payplug\Payment::retrieve($document->getPayment()->getTokenPayment());

            if($payment->is_paid){

                $this->updatePaiementAndUpdateDocumentToBePrepared($payment, $document, $docParams);

                $response['paiement'] = true;
                return $response;

            }else{

                //document non payé
                return new RedirectResponse($this->router->generate('paiement_canceled'));

            }
        }
    }

    public function notificationUrlWithHelloAsso($token)
    {
        $document = $this->documentRepository->findOneBy(['token' => $token]);

        //si on trouve le document et pas de numero de facture
        if($document AND is_null($document->getBillNumber())){

            $docParams = $this->documentParametreRepository->findOneBy([]);

            $bearer = $this->helloAssoAuth();

            $payment = $document->getPayment();

            $result = $this->client->request('GET', $_ENV['HELLO_ASSO_URL_API'].'/'.$payment->getTokenPayment(),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$bearer
                ]
            ]);

            //on recupere la reponse du serveur
            $content = $result->toArray();

            //s'il y a eu enregistrement chez HelloAsso
            if(isset($content['order']))
            {
                $order = $content['order'];
                
                //paiement accepter
                if($order['payments'][0]['state'] == "Authorized")
                {
                    $response['paiement'] = true;
                    //il faut transformer la date du paiement en timestamp
                    $timestampFromPayment = strtotime($order['payments'][0]['date']);

                    $payment->setMeansOfPayment($this->meansOfPayementRepository->findOneBy(['name' => 'CB']))->setTimeOfTransaction($this->utilities->getDateTimeImmutableFromTimestamp($timestampFromPayment))->setDetails('Paiement par CB');
                    $this->em->persist($payment);
                    $this->em->flush();
        
                    $newNumero = $this->documentService->generateNewNumberOf('billNumber', 'getBillNumber');
                    //on met a jour le document en BDD
                    $etat = $this->documentStatusRepository->findOneBy(['action' => 'TO_PREPARE']);
                    $document = $payment->getDocument();
                    $document->setDocumentStatus($etat)->setBillNumber($docParams->getBillingTag().$newNumero);
                    $this->em->persist($document);
                    $this->em->flush();

                    //on envoye le mail au client
                    $this->mailService->sendMail(true, $document->getUser()->getEmail(), 'Merci pour votre commande', 'paiementOk', ['document' => $document], 'noreply@refaitesvosjeux.fr', true);

                }

            }

        }
    }

    public function notificationUrlWithPayplug($token)
    {
        $docParams = $this->documentParametreRepository->findOneBy([]);
        
        //on s'identifie
        $this->payplugAuth();
        
        $input = file_get_contents('php://input');


        try{
            $resource = \Payplug\Notification::treat($input);

                if($resource instanceof \Payplug\Resource\Payment && $resource->is_paid) {
                    // Process a paid payment.

                    $payment_id = $resource->id;

                    //on retrouve le paiement et le document
                    $paiement = $this->paymentRepository->findOneBy(['tokenPayment' => $payment_id]);
                    $document = $paiement->getDocument();

                    $this->updatePaiementAndUpdateDocumentToBePrepared($resource, $document, $docParams);

                }
        }
        catch (\Payplug\Exception\PayplugException $exception) {
            echo htmlentities($exception);
        }
    }

    public function notificationUrlWithStripe($token)
    {
        throw new Exception('function notificationUrlWithStripe in paiementService NOT INFORM');
    }

    private function explodeAdresse($adresse,$document)
    {        

        $adresseExploded = explode("<br/>", $adresse);
        $arrayAdresse = [];

        //si on a une association
        if(count($adresseExploded) > 4){
            $arrayAdresse['title'] = $adresseExploded[0]; //association
            $first_last = explode(" ", $adresseExploded[1]); // prénom et nom
            $arrayAdresse['first_name']  = $first_last[0];
            $arrayAdresse['last_name'] = $first_last[1];
            $arrayAdresse['email'] = $document->getUser()->getEmail();
            $arrayAdresse['adresse1'] = $adresseExploded[2];
            $postal_ville = explode(" ", $adresseExploded[3]);
            $arrayAdresse['postCode'] = $postal_ville[0];
            $arrayAdresse['city'] = $postal_ville[1];
            $arrayAdresse['country'] = $adresseExploded[4];
            $arrayAdresse['language'] = 'fr';
        }else{
            $arrayAdresse['title'] = "Mr / Mme";
            $first_last = explode(" ", $adresseExploded[0]); // prénom et nom
            $arrayAdresse['first_name']  = $first_last[0];
            $arrayAdresse['last_name'] = $first_last[1];
            $arrayAdresse['email'] = $document->getUser()->getEmail();
            $arrayAdresse['adresse1'] = $adresseExploded[1];
            $postal_ville = explode(" ", $adresseExploded[2]);
            $arrayAdresse['postCode'] = $postal_ville[0];
            $arrayAdresse['city'] = $postal_ville[1];
            $arrayAdresse['country'] = $adresseExploded[3];
            $arrayAdresse['language'] = 'fr';
        }

        return $arrayAdresse;
    }
    
    public function payplugAuth()
    {
        \Payplug\Payplug::init(['secretKey' => $_ENV["PAYPLUG_SECRET"]]);
    }

    public function stripeAuth()
    {
        $stripe = new Stripe();
        $stripe->setApiKey($_ENV["STRIPE_SECRET"]);
    }

    public function helloAssoAuth()
    {

        $response = $this->client->request('POST', $_ENV['HELLO_ASSO_URL_TOKEN'], [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'accept' => 'application/json',
            ],
            'body' => [
                'client_id' => $_ENV['HELLO_ASSO_CLIENT_ID'],
                'grant_type' => 'client_credentials',
                'client_secret' => $_ENV['HELLO_ASSO_CLIENT_SECRET']
            ],
        ]);

        $content = $response->toArray();

        return $content['access_token'];

    }

    public function checkIfDocumentExistInDatabase(string $token):Document | RedirectResponse
    {

        $document = $this->documentRepository->findOneBy(['token' => $token, 'billNumber' => NULL, 'isDeleteByUser' => false]);

        if(!$document){

            $tableau = [
                'h1' => 'Document non trouvé !',
                'p1' => 'La consultation de ce document est impossible!',
                'p2' => 'Document inconnu ou supprimé !'
            ];

            return new RedirectResponse($this->router->generate('site/document_view/_end_view.html.twig', [
                'tableau' => $tableau
            ]));
        }

        return $document;
    }

    public function transformIsoCode2ToIsoCode3($isoCode2)
    {
        if(strlen($isoCode2) == 2){
            switch ($isoCode2) {
                case "FR":
                    $isoCode3 = "FRA";
                    break;
                case "BE":
                    $isoCode3 = "BEL";
                    break;
                default:
                    $isoCode3 = "N/A";
            }
        }

        return $isoCode3;
    }

    public function getHelloAssoPaiementStatus($bearer, Payment $payment)
    {

        $result = $this->client->request('GET', 'https://api.helloasso.com/v5/organizations/refaites-vos-jeux/checkout-intents/'.$payment->getTokenPayment(),
        [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$bearer
            ]
        ]);

        return $result;
    }

    public function updateDocumentAndPaiementWithHelloAssoStatus(Document $document)
    {

        $docParams = $this->documentParametreRepository->findOneBy([]);

        $bearer = $this->helloAssoAuth();

        $payment = $document->getPayment();

        $result = $this->client->request('GET', $_ENV['HELLO_ASSO_URL_API'].'/'.$payment->getTokenPayment(),
        [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$bearer
            ]
        ]);

        //on recupere la reponse du serveur
        $content = $result->toArray();

        //s'il y a eu enregistrement chez HelloAsso
        if(isset($content['order']))
        {
            $order = $content['order'];

            //paiement accepter
            if($order['payments'][0]['state'] == "Authorized")
            {
                $response['paiement'] = true;
                //il faut transformer la date du paiement en timestamp
                $timestampFromPayment = strtotime($order['payments'][0]['date']);

                $payment->setMeansOfPayment($this->meansOfPayementRepository->findOneBy(['name' => 'CB']))->setTimeOfTransaction($this->utilities->getDateTimeImmutableFromTimestamp($timestampFromPayment))->setDetails('Paiement par CB');
                $this->em->persist($payment);
                $this->em->flush();
    
                $newNumero = $this->documentService->generateNewNumberOf('billNumber', 'getBillNumber');
                //on met a jour le document en BDD
                $etat = $this->documentStatusRepository->findOneBy(['action' => 'TO_PREPARE']);

                //si pas de numéro de facture
                if($document->getBillNumber() == NULL){
                    $document->setDocumentStatus($etat)->setBillNumber($docParams->getBillingTag().$newNumero);
                }
                $this->em->persist($document);
                $this->em->flush();

                $this->mailService->sendMail(true, $document->getUser()->getEmail(), 'Commande réceptionnée', 'paiementOk', ['document' => $document, 'legales' => $this->legalInformationRepository->findOneBy([])], 'noreply@refaitesvosjeux.fr', true);
                
            }
        }

    }

    public function paiementSuccessWithHelloAsso($tokenDocument)
    {
        $document = $this->documentRepository->findOneBy(['token' => $tokenDocument]);
        $response = [];
        
        $session = $this->requestStack->getSession();

        //on reset les paniers
        $paniers['occasions'] = [];
        $paniers['items'] = [];
        $paniers['boites'] = [];
        $session->set('paniers', $paniers);
        $response['paiement'] = false;
        $response['route'] = 'app_home';

        if(!$document){
            //pas de devis
            $response['messageFlash'] = 'Document inconnu!';

        }else if(!is_null($document->getBillNumber())){
            //document deja facturé
            $response['paiement'] = true;

        }else if(is_null($document->getBillNumber())){

            $docParams = $this->documentParametreRepository->findOneBy([]);

            $bearer = $this->helloAssoAuth();

            $payment = $document->getPayment();

            $result = $this->client->request('GET', $_ENV['HELLO_ASSO_URL_API'].'/'.$payment->getTokenPayment(),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$bearer
                ]
            ]);

            //on recupere la reponse du serveur
            $content = $result->toArray();

            //s'il y a eu enregistrement chez HelloAsso
            if(isset($content['order']))
            {
                $order = $content['order'];
                
                //paiement accepter
                if($order['payments'][0]['state'] == "Authorized")
                {
                    $response['paiement'] = true;
                    //il faut transformer la date du paiement en timestamp
                    $timestampFromPayment = strtotime($order['payments'][0]['date']);

                    $payment->setMeansOfPayment($this->meansOfPayementRepository->findOneBy(['name' => 'CB']))->setTimeOfTransaction($this->utilities->getDateTimeImmutableFromTimestamp($timestampFromPayment))->setDetails('Paiement par CB');
                    $this->em->persist($payment);
                    $this->em->flush();
        
                    $newNumero = $this->documentService->generateNewNumberOf('billNumber', 'getBillNumber');
                    //on met a jour le document en BDD
                    $etat = $this->documentStatusRepository->findOneBy(['action' => 'TO_PREPARE']);
                    $document = $payment->getDocument();
                    $document->setDocumentStatus($etat)->setBillNumber($docParams->getBillingTag().$newNumero);
                    $this->em->persist($document);
                    $this->em->flush();

                    $this->mailService->sendMail(true, $document->getUser()->getEmail(), 'Commande réceptionnée', 'paiementOk', ['document' => $document, 'legales' => $this->legalInformationRepository->findOneBy([])], 'noreply@refaitesvosjeux.fr', true);
                    
                }

            }else{
                
                //pas d'enregistrement
                $response['route'] = $content['redirectUrl'];
            }

        }

        return $response;
    }

    public function updatePaiementAndUpdateDocumentToBePrepared($payment, $document, $docParams)
    {

        $payment_date = $this->utilities->getDateTimeImmutableFromTimestamp($payment->hosted_payment->paid_at);
        $card = $payment->card->brand.'(***** '.$payment->card->last4.' - '.$payment->card->exp_month.'/'.$payment->card->exp_year.')';

        //on retrouve le paiement deja lier
        $paiement = $document->getPayment();

        //il faut creer le numero de facture
        $newNumero = $this->documentService->generateNewNumberOf('billNumber', 'getBillNumber');

        //on renseigne le paiement
        $paiement->setDetails($card)->setTimeOfTransaction($payment_date);
        //on sauvegarde le paiement
        $this->em->persist($paiement);
        $this->em->flush();
        
        //on met a jour le document en BDD
        $etat = $this->documentStatusRepository->findOneBy(['action' => 'TO_PREPARE']);
        $document->setDocumentStatus($etat)->setBillNumber($docParams->getBillingTag().$newNumero);
        $this->em->persist($document);
        $this->em->flush();

    }

    public function importPaiements(SymfonyStyle $io): void
    {
        $io->title('Importation des paiements');

        $docs = $this->readCsvFileDocuments();

        foreach($docs as $arrayDoc){

            $num_transaction = $this->utilitiesService->stringToNull($arrayDoc['num_transaction']);

            if(!is_null($num_transaction)){

                $paiement = $this->createOrUpdatePaiement($arrayDoc);

                $this->em->persist($paiement);
            }
        }

        $this->em->flush();
        $io->success('Importation terminée');

    }

    //lecture des fichiers exportes dans le dossier import
    private function readCsvFileDocuments(): Reader
    {
        $csvDocuments = Reader::createFromPath('%kernel.root.dir%/../import/_table_documents.csv','r');
        $csvDocuments->setHeaderOffset(0);

        return $csvDocuments;
    }

    private function createOrUpdatePaiement(array $arrayDoc): Payment
    {
        $document = $this->documentRepository->findOneBy(['rvj2id' => $arrayDoc['idDocument']]);

        $paiement = $this->paymentRepository->findOneBy(['document' => $document]);

        if(!$paiement){
            $paiement = new Payment();
        }

        //?cohérence mouvement ESPECES partout
        if($arrayDoc['moyen_paiement'] == 'ESP')
        {
            $moyenPaiement = 'ESPÈCES';

        }elseif($arrayDoc['moyen_paiement'] == 'NULL')
        {//?il peut y avoir ce cas

            $moyenPaiement = 'EN COURS';

        }else{

            $moyenPaiement = $arrayDoc['moyen_paiement'];

        }

        $paiement
        ->setTokenPayment($arrayDoc['num_transaction'])
        ->setDocument($document)
        ->setMeansOfPayment($this->meansOfPayementRepository->findOneBy(['name' => $moyenPaiement]))
        ->setCreatedAt($this->utilitiesService->getDateTimeImmutableFromTimestamp($arrayDoc['time_transaction']))
        ->setTimeOfTransaction($this->utilitiesService->getDateTimeImmutableFromTimestamp($arrayDoc['time_transaction']));

        return $paiement;
    }

}