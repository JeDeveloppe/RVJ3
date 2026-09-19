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

        //?le client a pu regler l'ancien checkout pendant qu'on en cree un nouveau : on garde son identifiant
        //?pour que la verification (retour client, notification, connexion admin) le controle aussi
        if($paiement->getTokenPayment() && $paiement->getTokenPayment() !== $content['id']){
            $paiement->addPreviousTokenPayment($paiement->getTokenPayment());
        }

        //dans tous les cas une entity paiement est creee
        $paiement->setDocument($document)
                ->setTokenPayment($content['id'])
                // ->setMeansOfPayment($this->meansOfPayementRepository->findOneBy(['name' => 'CB']))
                ->setMeansOfPayment(null)
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
    
    public function paiementSuccessWithPayplug(string $token)
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
            $this->updateDocumentAndPaiementWithHelloAssoStatus($document);
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

    //réconciliation des paiements HelloAsso, appelée à la connexion admin (avant la suppression des devis expirés)
    //et à chaque notification HelloAsso. Retourne false si l'authentification à l'API HelloAsso a échoué.
    public function verifyHelloAssoPayments(): bool
    {
        return $this->reconcileHelloAssoPayments()['authenticated'];
    }

    /**
     * Vérifie auprès de HelloAsso tous les documents non facturés dont un paiement a été initié.
     * Utilisé aussi par le bouton "Forcer une vérification maintenant" de l'admin, qui affiche ce détail.
     *
     * Avec $withPaymentMatching (bouton admin uniquement, jamais en automatique), les devis restés
     * impayés sont ensuite rapprochés des paiements autorisés chez HelloAsso (e-mail + montant + date) :
     * filet de sécurité quand l'identifiant du checkout a été perdu (cf. Payment::previousTokenPayments).
     *
     * @return array{authenticated: bool, paid: string[], matched: string[], ambiguous: string[], unpaid: string[], errors: string[], matchingFailed: bool} numéros de devis
     */
    public function reconcileHelloAssoPayments(bool $withPaymentMatching = false): array
    {
        $result = ['authenticated' => true, 'paid' => [], 'matched' => [], 'ambiguous' => [], 'unpaid' => [], 'errors' => [], 'matchingFailed' => false];

        //seuls les documents dont un paiement a été initié peuvent avoir été réglés
        $documents = array_filter(
            $this->documentRepository->findDocumentsNotBilled(),
            fn(Document $document) => $document->getPayment() && $document->getPayment()->getTokenPayment()
        );

        if(count($documents) === 0){
            return $result;
        }

        try {
            $bearer = $this->helloAssoAuth();
        } catch (\Throwable $e) {
            error_log('reconcileHelloAssoPayments: authentification HelloAsso impossible: '.$e->getMessage());
            $result['authenticated'] = false;
            return $result;
        }

        $unpaidDocuments = [];

        foreach($documents as $document){
            try {
                $status = $this->reconcileHelloAssoDocument($document, $bearer);
            } catch (\Throwable $e) {
                error_log('reconcileHelloAssoPayments error for document '.$document->getId().': '.$e->getMessage());
                $status = 'error';
            }

            if($status === 'unpaid'){
                $unpaidDocuments[] = $document;
            }

            $key = ['paid' => 'paid', 'unpaid' => 'unpaid'][$status] ?? 'errors';
            $result[$key][] = $document->getQuoteNumber();
        }

        if($withPaymentMatching && count($unpaidDocuments) > 0){
            try {
                $matching = $this->matchUnpaidDocumentsWithHelloAssoPayments($unpaidDocuments, $bearer);

                $result['matched'] = $matching['matched'];
                $result['ambiguous'] = $matching['ambiguous'];
                //un devis rapproché ou ambigu n'est plus simplement "impayé"
                $result['unpaid'] = array_values(array_diff($result['unpaid'], $matching['matchedQuotes'], $matching['ambiguous']));
            } catch (\Throwable $e) {
                error_log('reconcileHelloAssoPayments matching error: '.$e->getMessage());
                $result['matchingFailed'] = true;
            }
        }

        return $result;
    }

    /**
     * Rapproche des devis impayés (identifiant de checkout perdu ou jamais réglé) les paiements
     * "Authorized" de HelloAsso, par un lien exact et non par déduction :
     * paiement -> sa commande (order.id) -> le checkout d'origine (checkoutIntentId) -> notre
     * metadata.reference (numéro de devis, envoyé à la création du checkout).
     * Un devis n'est marqué payé que si UN SEUL paiement porte sa référence ET que le montant est
     * identique ; sinon il est signalé "ambigu" et rien n'est modifié.
     *
     * @param Document[] $documents
     * @return array{matched: string[], matchedQuotes: string[], ambiguous: string[]}
     */
    private function matchUnpaidDocumentsWithHelloAssoPayments(array $documents, string $bearer): array
    {
        $out = ['matched' => [], 'matchedQuotes' => [], 'ambiguous' => []];

        //les devis trop anciens sont ignorés (supprimés de toute façon par le nettoyage des devis expirés)
        $oldestAllowed = new DateTimeImmutable('-60 days');
        $documentsByQuote = [];
        foreach($documents as $document){
            if($document->getCreatedAt() && $document->getCreatedAt() >= $oldestAllowed && $document->getQuoteNumber()){
                $documentsByQuote[$document->getQuoteNumber()] = $document;
            }
        }

        if(count($documentsByQuote) === 0){
            return $out;
        }

        $from = min(array_map(fn(Document $document) => $document->getCreatedAt(), $documentsByQuote))->modify('-1 day');

        //garde-fou : 2 appels API par paiement examiné
        $helloAssoPayments = array_slice($this->fetchAuthorizedHelloAssoPaymentsSince($bearer, $from), 0, 50);

        //paiements retrouvés par devis : [numéro de devis => [[paiement, checkoutIntentId], ...]]
        $found = [];
        foreach($helloAssoPayments as $helloAssoPayment){
            $timestamp = strtotime($helloAssoPayment['date'] ?? '');
            $orderId = $helloAssoPayment['order']['id'] ?? null;
            if($timestamp === false || $orderId === null || ($helloAssoPayment['order']['formType'] ?? null) !== 'Checkout'){
                continue;
            }

            //paiement déjà rattaché à une commande
            $paidAt = $this->utilities->getDateTimeImmutableFromTimestamp($timestamp);
            if($this->paymentRepository->isHelloAssoPaymentAlreadyRecorded((string) $helloAssoPayment['id'], $paidAt)){
                continue;
            }

            [$checkoutIntentId, $reference] = $this->getHelloAssoCheckoutOfOrder($bearer, $orderId);

            if($reference !== null && isset($documentsByQuote[$reference])){
                $found[$reference][] = ['payment' => $helloAssoPayment, 'checkoutIntentId' => $checkoutIntentId];
            }
        }

        foreach($found as $quoteNumber => $candidates){
            $document = $documentsByQuote[$quoteNumber];

            if(count($candidates) === 1 && (int) $candidates[0]['payment']['amount'] === (int) $document->getTotalWithTax()){
                $helloAssoPayment = $candidates[0]['payment'];
                //on enregistre le checkout réellement réglé (l'ancien identifiant passe dans l'historique)
                $this->markDocumentAsPaidWithHelloAsso($document, $document->getPayment(), (string) $candidates[0]['checkoutIntentId'], $helloAssoPayment, 'Paiement par CB (rapprochement HelloAsso n°'.$helloAssoPayment['id'].')');

                $out['matched'][] = $quoteNumber.' (paiement HelloAsso n°'.$helloAssoPayment['id'].')';
                $out['matchedQuotes'][] = $quoteNumber;
            }else{
                //plusieurs paiements pour le même devis, ou montant différent : décision humaine
                $out['ambiguous'][] = $quoteNumber;
            }
        }

        return $out;
    }

    /**
     * Checkout d'origine d'une commande HelloAsso : [checkoutIntentId, metadata.reference] (null si introuvable).
     * Le paiement ne porte pas ce lien (seule la commande expose checkoutIntentId), d'où les 2 appels.
     */
    private function getHelloAssoCheckoutOfOrder(string $bearer, int|string $orderId): array
    {
        try {
            //l'adresse de l'API commandes n'est pas sous /organizations/{slug} : on repart de la racine v5
            $orderUrl = preg_replace('#/organizations/[^/]+/checkout-intents/?$#', '/orders/'.$orderId, $_ENV['HELLO_ASSO_URL_API']);

            $headers = ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$bearer];

            $order = $this->client->request('GET', $orderUrl, ['headers' => $headers])->toArray();
            $checkoutIntentId = $order['checkoutIntentId'] ?? null;
            if($checkoutIntentId === null){
                return [null, null];
            }

            $intent = $this->getHelloAssoCheckoutIntent($bearer, (string) $checkoutIntentId);

            return [$checkoutIntentId, $intent['metadata']['reference'] ?? null];
        } catch (\Exception $e) {
            error_log('getHelloAssoCheckoutOfOrder error for order '.$orderId.': '.$e->getMessage());
            return [null, null];
        }
    }

    /**
     * Paiements "Authorized" de l'organisation depuis une date (pagination par curseur chez HelloAsso).
     * L'adresse est déduite de HELLO_ASSO_URL_API (.../organizations/{slug}/checkout-intents).
     */
    private function fetchAuthorizedHelloAssoPaymentsSince(string $bearer, DateTimeImmutable $from, ?DateTimeImmutable $to = null): array
    {
        $url = preg_replace('#/checkout-intents/?$#', '/payments', $_ENV['HELLO_ASSO_URL_API']);
        $pageSize = 100;
        $payments = [];
        $continuationToken = null;

        //garde-fou : 10 pages de 100 paiements au maximum
        for($page = 0; $page < 10; $page++){

            $query = ['states' => 'Authorized', 'from' => $from->format('Y-m-d\TH:i:s'), 'pageSize' => $pageSize];
            if($to !== null){
                $query['to'] = $to->format('Y-m-d\\TH:i:s');
            }
            if($continuationToken){
                $query['continuationToken'] = $continuationToken;
            }

            $content = $this->client->request('GET', $url,
            [
                'query' => $query,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$bearer
                ]
            ])->toArray();

            $data = $content['data'] ?? [];
            foreach($data as $helloAssoPayment){
                //indexé par numéro : un même paiement renvoyé deux fois ne doit pas apparaître comme deux candidats
                if(isset($helloAssoPayment['id'])){
                    $payments[$helloAssoPayment['id']] = $helloAssoPayment;
                }
            }

            $continuationToken = $content['pagination']['continuationToken'] ?? null;
            if(count($data) < $pageSize || !$continuationToken){
                break;
            }
        }

        return array_values($payments);
    }

    //Vérifie auprès de HelloAsso le paiement d'un document (identifiant courant ET anciens identifiants)
    //et, s'il est autorisé, passe le document en "à préparer". Retourne true si le document est payé.
    public function updateDocumentAndPaiementWithHelloAssoStatus(Document $document, ?string $bearer = null): bool
    {

        $payment = $document->getPayment();

        //pas de paiement lié à ce document (paiement jamais initié ou moyen de paiement autre que HelloAsso)
        if(!$payment || !$payment->getTokenPayment()){
            return false;
        }

        return $this->reconcileHelloAssoDocument($document, $bearer ?? $this->helloAssoAuth()) === 'paid';
    }

    //Contrôle l'identifiant courant ET les anciens. Retourne 'paid' (document mis à jour), 'unpaid'
    //(rien d'autorisé chez HelloAsso) ou 'error' (l'API a échoué et aucun paiement autorisé n'a été trouvé).
    private function reconcileHelloAssoDocument(Document $document, string $bearer): string
    {
        $hasError = false;

        foreach($document->getPayment()->getAllTokenPayments() as $tokenPayment){

            try {
                $content = $this->getHelloAssoCheckoutIntent($bearer, $tokenPayment);
            } catch (\Exception $e) {
                //token de paiement invalide pour HelloAsso (autre moyen de paiement, erreur API...) : on passe au suivant
                error_log('reconcileHelloAssoDocument error for document '.$document->getId().': '.$e->getMessage());
                $hasError = true;
                continue;
            }

            $helloAssoPayment = $this->findAuthorizedHelloAssoPayment($content);

            if($helloAssoPayment !== null){
                $this->markDocumentAsPaidWithHelloAsso($document, $document->getPayment(), $tokenPayment, $helloAssoPayment);
                return 'paid';
            }
        }

        return $hasError ? 'error' : 'unpaid';
    }

    /**
     * Retrouve chez HelloAsso le paiement autorisé d'un Payment du site (identifiant de checkout courant ET anciens).
     * Ne modifie rien. Les identifiants non numériques (ex. "AUCUN", "RefaitesVosJeuxManuel" : paiement saisi à la main)
     * ne sont pas des checkouts HelloAsso et ne sont pas interrogés.
     *
     * @return array{status: string, paymentId?: string, tokenPayment?: string, amount?: int, message?: string} status : 'found' | 'unpaid' | 'manual' | 'error'
     */
    public function lookupHelloAssoPayment(Payment $payment, string $bearer): array
    {
        $tokens = array_values(array_filter($payment->getAllTokenPayments(), fn(string $token) => ctype_digit($token)));

        if(count($tokens) === 0){
            return ['status' => 'manual'];
        }

        $lastError = null;

        foreach($tokens as $tokenPayment){
            try {
                $content = $this->getHelloAssoCheckoutIntent($bearer, $tokenPayment);
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                continue;
            }

            $helloAssoPayment = $this->findAuthorizedHelloAssoPayment($content);
            if($helloAssoPayment !== null && isset($helloAssoPayment['id'])){
                return ['status' => 'found', 'paymentId' => (string) $helloAssoPayment['id'], 'tokenPayment' => $tokenPayment, 'amount' => (int) ($helloAssoPayment['amount'] ?? 0)];
            }
        }

        return $lastError !== null ? ['status' => 'error', 'message' => $lastError] : ['status' => 'unpaid'];
    }

    /**
     * Retrouve le paiement HelloAsso d'un document par le lien exact (paiement -> commande -> checkout d'origine ->
     * metadata.reference = numéro de devis), dans une fenêtre de ±1 jour autour d'une date. Utile quand l'identifiant
     * de checkout enregistré n'est pas celui qui a été réglé. Ne modifie rien.
     *
     * @return array{paymentId: string, amount: int, checkoutIntentId: string}|null
     */
    public function lookupHelloAssoPaymentByReference(Document $document, string $bearer, DateTimeImmutable $around): ?array
    {
        $helloAssoPayments = $this->fetchAuthorizedHelloAssoPaymentsSince($bearer, $around->modify('-1 day'), $around->modify('+1 day'));

        foreach($helloAssoPayments as $helloAssoPayment){
            if(($helloAssoPayment['order']['formType'] ?? null) !== 'Checkout' || !isset($helloAssoPayment['order']['id'])){
                continue;
            }

            [$checkoutIntentId, $reference] = $this->getHelloAssoCheckoutOfOrder($bearer, $helloAssoPayment['order']['id']);

            if($reference !== null && $reference === $document->getQuoteNumber()){
                return ['paymentId' => (string) $helloAssoPayment['id'], 'amount' => (int) ($helloAssoPayment['amount'] ?? 0), 'checkoutIntentId' => (string) $checkoutIntentId];
            }
        }

        return null;
    }

    private function getHelloAssoCheckoutIntent(string $bearer, string $tokenPayment): array
    {
        $result = $this->client->request('GET', $_ENV['HELLO_ASSO_URL_API'].'/'.$tokenPayment,
        [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$bearer
            ]
        ]);

        return $result->toArray();
    }

    //s'il y a eu enregistrement chez HelloAsso, retourne le premier paiement à l'état "Authorized"
    private function findAuthorizedHelloAssoPayment(array $content): ?array
    {
        foreach($content['order']['payments'] ?? [] as $helloAssoPayment){
            if(($helloAssoPayment['state'] ?? null) === 'Authorized'){
                return $helloAssoPayment;
            }
        }

        return null;
    }

    private function markDocumentAsPaidWithHelloAsso(Document $document, Payment $payment, string $tokenPayment, array $helloAssoPayment, string $details = 'Paiement par CB'): void
    {
        //un autre passage (retour client, notification, connexion admin) a pu facturer entre-temps
        $this->em->refresh($document);
        if($document->getBillNumber() !== null){
            return;
        }

        $docParams = $this->documentParametreRepository->findOneBy([]);

        //on garde l'identifiant du checkout réellement réglé
        if($payment->getTokenPayment() !== $tokenPayment){
            $payment->addPreviousTokenPayment($payment->getTokenPayment());
            $payment->setTokenPayment($tokenPayment);
        }

        //il faut transformer la date du paiement en timestamp
        $timestampFromPayment = strtotime($helloAssoPayment['date'] ?? 'now');

        $payment->setMeansOfPayment($this->meansOfPayementRepository->findOneBy(['name' => 'CB']))->setTimeOfTransaction($this->utilities->getDateTimeImmutableFromTimestamp($timestampFromPayment))->setDetails($details);
        //numéro du paiement chez HelloAsso (celui de leur back-office)
        if(isset($helloAssoPayment['id'])){
            $payment->setHelloAssoPaymentId((string) $helloAssoPayment['id']);
        }
        $this->em->persist($payment);
        $this->em->flush();

        $newNumero = $this->documentService->generateNewNumberOf('billNumber', 'getBillNumber');
        //on met a jour le document en BDD
        $etat = $this->documentStatusRepository->findOneBy(['action' => 'TO_PREPARE']);
        $document->setDocumentStatus($etat)->setBillNumber($docParams->getBillingTag().$newNumero);
        $this->em->persist($document);
        $this->em->flush();

        $this->mailService->sendMail(true, $document->getUser()->getEmail(), 'Commande réceptionnée', 'paiementOk', ['document' => $document, 'legales' => $this->legalInformationRepository->findOneBy([])], 'noreply@refaitesvosjeux.fr', true);
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

            try {

                if($this->updateDocumentAndPaiementWithHelloAssoStatus($document)){
                    $response['paiement'] = true;
                }else{
                    //pas de paiement autorisé : si HelloAsso n'a rien enregistré, on renvoie le client sur sa page de paiement
                    $redirectUrl = $this->getHelloAssoRedirectUrlWhenNothingRegistered($document->getPayment());
                    if($redirectUrl){
                        $response['redirectUrl'] = $redirectUrl;
                    }
                }

            } catch (\Throwable $e) {
                //?ne jamais faire planter la page de retour : le paiement sera repris par la notification ou la connexion admin
                error_log('paiementSuccessWithHelloAsso error for document '.$document->getId().': '.$e->getMessage());
            }

        }

        return $response;
    }

    //?URL de la page de paiement HelloAsso du checkout courant, uniquement si HelloAsso n'a encore enregistré aucune commande dessus
    private function getHelloAssoRedirectUrlWhenNothingRegistered(?Payment $payment): ?string
    {
        if(!$payment || !$payment->getTokenPayment()){
            return null;
        }

        $content = $this->getHelloAssoCheckoutIntent($this->helloAssoAuth(), $payment->getTokenPayment());

        if(!isset($content['order']) && isset($content['redirectUrl'])){
            return $content['redirectUrl'];
        }

        return null;
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