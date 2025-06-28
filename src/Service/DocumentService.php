<?php

namespace App\Service;

use DateInterval;
use DateTimeZone;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use App\Entity\Panier;
use DateTimeImmutable;
use League\Csv\Reader;
use App\Entity\Address;
use App\Entity\Payment;
use App\Entity\Reserve;
use App\Entity\Document;
use App\Entity\DocumentLine;
use App\Entity\DocumentStatus;
use App\Entity\ShippingMethod;
use App\Repository\TaxRepository;
use App\Service\UtilitiesService;
use App\Repository\ItemRepository;
use App\Repository\UserRepository;
use App\Entity\OffSiteOccasionSale;
use App\Entity\QuoteRequest;
use App\Entity\Returndetailstostock;
use App\Entity\User;
use App\Repository\AddressRepository;
use App\Repository\PaymentRepository;
use App\Repository\DeliveryRepository;
use App\Repository\DocumentRepository;
use App\Repository\OccasionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\DocumentLineRepository;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\DocumentStatusRepository;
use App\Repository\ShippingMethodRepository;
use App\Repository\CollectionPointRepository;
use App\Repository\CountryRepository;
use App\Repository\DocumentsendingRepository;
use App\Repository\MeansOfPayementRepository;
use App\Repository\VoucherDiscountRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\LegalInformationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use App\Repository\DocumentParametreRepository;
use App\Repository\OffSiteOccasionSaleRepository;
use Proxies\__CG__\App\Entity\CollectionPoint as EntityCollectionPoint;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DocumentService
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentStatusRepository $documentStatusRepository,
        private DocumentParametreRepository $documentParametreRepository,
        private DocumentLineRepository $documentLineRepository,
        private Security $security,
        private AdresseService $adresseService,
        private AddressRepository $addressRepository,
        private UtilitiesService $utilitiesService,
        private ItemRepository $itemRepository,
        private LegalInformationRepository $legalInformationRepository,
        private OccasionRepository $occasionRepository,
        private EntityManagerInterface $em,
        private PaymentRepository $paymentRepository,
        private Environment $twig,
        private UserRepository $userRepository,
        private ParameterBagInterface $parameter,
        private MailService $mailService,
        private RouterInterface $router,
        private ShippingMethodRepository $shippingMethodRepository,
        private CollectionPointRepository $collectionPointRepository,
        private VoucherDiscountRepository $voucherDiscountRepository,
        private DocumentsendingRepository $documentsendingRepository,
        private TaxRepository $taxRepository,
        private OffSiteOccasionSaleRepository $offSiteOccasionSaleRepository,
        private DeliveryRepository $deliveryRepository,
        private RequestStack $requestStack,
        private MeansOfPayementRepository $meansOfPayementRepository,
        private CountryRepository $countryRepository,
        ){
    }

    public function generateNewNumberOf($column, $methode)
    {

        $dateTimeImmutable = new DateTimeImmutable('now');
        $yearForSearchInDatabase = $dateTimeImmutable->format('Y');
        $year = $dateTimeImmutable->format('y');  //format du numero => y pour 23  Y pour 2023
        $month = $dateTimeImmutable->format('m');

        //il faudra trouver le dernier document de la base et incrementer de 1 pour le document
        $lastDocumentByYear = $this->documentRepository->findLastEntryFromThisYear($column, $yearForSearchInDatabase);
        //si pas d'entree alors nouvelle annee
        if(count($lastDocumentByYear) == 0){
            
            $numero = 1;

        }else{

            switch ($methode) {
                case 'getQuoteNumber':
                //dernier entree on recupere le numero de devis DEV23090472
                $numero = substr($lastDocumentByYear[0]->getQuoteNumber(), -4) + 1; //2022010001 reste 0001 + 1
                break;  
                case 'getBillNumber':
                //dernier entree on recupere le numero de facture DEV23090472
                $numero = substr($lastDocumentByYear[0]->getBillNumber(), -4) + 1; //2022010001 reste 0001 + 1
                break;

                default:
                throw new \Exception('Methode inconnue');
                break;

            }

            //on signal que le document precedent est supprimable
            $lastDocumentByYear[0]->setIsLastQuote(false);
            $this->em->persist($lastDocumentByYear[0]);
            $this->em->flush();

        }

        return $this->numberConstruction($numero,$year,$month);

    }

    public function numberConstruction($numero,$year,$month)
    {
        
        if($numero == 1){ //premier enregistrement de l'annee
            return $year.$month.'0001';
        }else{
            $longueur = strlen($numero); //dernier enregistrement

            if($longueur < 2){                        //moins de 10
                    $numeroCreer = $year.$month."000".$numero;
            }else if($longueur == 2){                 //de 10 à 99
                    $numeroCreer = $year.$month."00".$numero;
            }else if($longueur == 3){                 //de 100 à 999
                    $numeroCreer = $year.$month."0".$numero;
            }else if($longueur == 4){                 //de 1000 à 9999
                    $numeroCreer = $year.$month.$numero;
            }

            return $numeroCreer;
        }
    }

    public function saveDocumentLogicInDataBase(array $panierParams, Session $session, Request $request, ?QuoteRequest $quoteRequest = NULL):Document
    {

        $document = $this->generateDocument($panierParams, $session, $quoteRequest);
        $this->generateAllLinesFromPanierIntoDocumentLines($panierParams, $document);

        $paniers = $session->get('paniers');
        $request->cookies->remove($paniers['shippingMethodId']);

        return $document;
    }

    public function generateDocument(array $panierParams, Session $session, ?QuoteRequest $quoteRequest = NULL):Document
    {

        $paniers = $session->get('paniers');
        
        $billingAddress = $this->addressRepository->find($paniers['billingAddressId']);
        $shippingMethod = $this->shippingMethodRepository->find($paniers['shippingMethodId']);

        if($shippingMethod->getPrice() == "PAYANT"){

            $deliveryAddress = $this->addressRepository->find($paniers['deliveryAddressId']);

        }else{

            $deliveryAddress = $this->collectionPointRepository->find($paniers['deliveryAddressId']);
        }
        
        //ON genere un nouveau numero
        $quoteNumber = $this->generateNewNumberOf("quoteNumber", "getQuoteNumber");
        //on cherche les parametres des documents
        $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);

        //puis on met dans la base
        $document = new Document();
        $now = new DateTimeImmutable('now');

        //?gestion entre quoteRequest et devis en live
        if($quoteRequest != null){
            $endDevis = $now->add(new DateInterval('P'.$docParams->getDelayBeforeDeleteQuoteRequest().'D'));
            $cost = 0;
        }else{
            $endDevis = $now->add(new DateInterval('P'.$docParams->getDelayBeforeDeleteDevis().'D'));
            $cost = $panierParams['preparationHt'];
        }

        $actionToSearch = $_ENV['DEVIS_NO_PAID_LABEL']; //? doit etre comme Service / importV2 / CreationDocumentStatusService
        
        $document
            ->setToken($this->utilitiesService->generateRandomString())
            ->setQuoteNumber($docParams->getQuoteTag().$quoteNumber)
            ->setTotalExcludingTax($panierParams['totalPanierHtAfterDelivery'])
            ->setUser($this->security->getUser())
            ->setDeliveryAddress($this->adresseService->constructAdresseForSaveInDatabase($deliveryAddress))
            ->setBillingAddress($this->adresseService->constructAdresseForSaveInDatabase($billingAddress))
            ->setTotalWithTax($this->utilitiesService->htToTTC($panierParams['totalPanierHtAfterDelivery'],$panierParams['tax']->getValue()))
            ->setDeliveryPriceExcludingTax($panierParams['deliveryCostWithoutTax'])
            ->setIsQuoteReminder(false)
            ->setEndOfQuoteValidation($endDevis)
            ->setCreatedAt($now)
            ->setIsLastQuote(true) //on met a true par defaut
            ->setTaxRate($panierParams['tax'])
            ->setTaxRateValue($panierParams['tax']->getValue())
            ->setCost($cost)
            ->setBillNumber(null)
            ->setIsDeleteByUser(false)
            ->setTimeOfSendingQuote(new DateTimeImmutable('now'))
            ->setDocumentStatus($this->documentStatusRepository->findOneBy(['action' => $actionToSearch]))
            ->setShippingMethod($shippingMethod);

        $this->em->persist($document);
        $this->em->flush();

        return $document;

    }

    public function generateAllLinesFromPanierIntoDocumentLines(array $panierParams, Document $document)
    {

        // $paniers = array_merge($panierParams['panier_occasions'],$panierParams['panier_boites'],$panierParams['panier_items']);
        $panier_occasions = $panierParams['panier_occasions'] ?? [];
        $panier_items = $panierParams['panier_items'] ?? [];
        $panier_boites = $panierParams['panier_boites'] ?? [];

        //ON TRAITE LES OCCASIONS
        foreach($panier_occasions as $panier){
            $documentLine = new DocumentLine();
            $documentLine
                ->setQuantity($panier->getQte() ?? 1)
                ->setOccasion($panier->getOccasion())
                ->setDocument($document)
                ->setPriceExcludingTax($panier->getPriceWithoutTax());
            $this->em->persist($documentLine);

            $occasion = $panier->getOccasion();
            $occasion->setIsOnline(false);
            $this->em->persist($occasion);

            //ON SUPPRIME CHAQUE PANIER OCCASION
            $this->em->remove($panier);
        }

        $this->em->flush();

        //ON TRAITE LES ITEMS
        foreach($panier_items as $panier){
            $documentLine = new DocumentLine();
            $documentLine
                ->setQuantity($panier->getQte() ?? 1)
                ->setItem($panier->getItem())
                ->setDocument($document)
                ->setPriceExcludingTax($panier->getPriceWithoutTax());
            $this->em->persist($documentLine);

            //ON SUPPRIME CHAQUE PANIER ITEM
            $this->em->remove($panier);
        }    

        $this->em->flush();


        //on met en BDD par ligne de document
        foreach($panier_boites as $panier){
            $documentLine = new DocumentLine();
            $documentLine
                ->setQuestion($panier->getQuestion())
                ->setAnswer($panier->getAnswer())
                ->setQuantity(1)
                ->setBoite($panier->getBoite())
                ->setDocument($document)
                ->setPriceExcludingTax($panier->getPriceExcludingTax());
                $this->em->persist($documentLine);
        }
        $this->em->flush();
    }

    public function deleteDocumentFromDataBaseAndPuttingItemsBoiteOccasionBackInStock(array $documentsToDelete = null)
    {
        $documentsWhithEndOfQuoteValidation = [];
        //si aucun document passe en parametre
        if(is_null($documentsToDelete)){
            $now = new DateTimeImmutable('now');
            $documentsWhithEndOfQuoteValidation = $this->documentRepository->findDocumentsToDeleteWhenEndOfQuoteValidationIsToOld($now);
        }

        $documentsWithDeleteByUser = $this->documentRepository->findDocumentsToDeleteWhenIsDeleteByUserAndIsNotTheLastInDatabase();

        $documentsToDelete = array_merge($documentsWhithEndOfQuoteValidation,$documentsWithDeleteByUser);

        foreach($documentsToDelete as $doc)
        {

            //?si c'est pas le dernier document on le supprime
            if($doc->getIsLastQuote() == false){

                $docLines = $this->documentLineRepository->findBy(['document' => $doc]);

                foreach($docLines as $docLine){

                    if(!is_null($docLine->getItem())){
                        $itemInDatabase = $this->itemRepository->find($docLine->getItem());
                        $itemInDatabase->setStockForSale($itemInDatabase->getStockForSale() + $docLine->getQuantity());
                        $this->em->persist($itemInDatabase);
                        $this->em->remove($docLine);
                    }

                    if(!is_null($docLine->getOccasion())){
                        $occasionInDatabase = $this->occasionRepository->find($docLine->getOccasion());
                        $occasionInDatabase->setIsOnline(true)->setIsBilled(false);
                        $this->em->persist($occasionInDatabase);
                        $this->em->remove($docLine);
                    }

                    if(!is_null($docLine->getBoite())){
                        $returnInStock = new Returndetailstostock();
                        $returnInStock->setDocument($doc->getQuoteNumber())
                            ->setQuestion($docLine->getQuestion())
                            ->setAnswer($docLine->getAnswer());
                        $this->em->persist($returnInStock);
                        $this->em->remove($docLine);
                    }
                }
                
                $this->em->remove($doc);

            }else{
                //?sinon on le declare supprimer par l'utilisateur
                $doc->setIsDeleteByUser(true);
                $this->em->persist($doc);
                $this->em->flush($doc);

            }
        }
        
        $this->em->flush();
    }

    public function generateValuesForDocument(Document $document): array
    {
        // Initialisation de la structure de données avec des valeurs par défaut pour chaque famille
        $donnees = [
            'items' => ['details' => [], 'totalWeigth' => 0, 'totalPriceExcludingTax' => 0],
            'occasions' => ['details' => [], 'totalWeigth' => 0, 'totalPriceExcludingTax' => 0],
            'boites' => ['details' => [], 'totalWeigth' => 0, 'totalPriceExcludingTax' => 0],
        ];

        // Récupérer toutes les lignes du document en une seule requête
        $allDocumentLines = $this->documentLineRepository->findBy(['document' => $document->getId()]);

        foreach ($allDocumentLines as $docLine) {
            $family = null; // Nom de la famille (corrigé la faute de frappe ici)

            // Déterminer la catégorie de la ligne du document
            if ($docLine->getItem() !== null) {
                $family = 'items';
            } elseif ($docLine->getOccasion() !== null) {
                $family = 'occasions';
            } elseif ($docLine->getBoite() !== null) {
                $family = 'boites';
            } else {
                // Gérer les cas où une ligne de document ne correspond à aucune catégorie
                // ou journaliser une erreur si cet état est inattendu
                continue;
            }

            if ($family) {
                $donnees[$family]['details'][] = $docLine;
                $donnees[$family]['totalPriceExcludingTax'] += $docLine->getPriceExcludingTax() * $docLine->getQuantity();

                $currentLineWeight = 0;
                if ($docLine->getBoite() !== null) {
                    $currentLineWeight = $docLine->getBoite()->getWeigth();
                } elseif ($docLine->getOccasion() !== null) {
                    // Supposons qu'une occasion a aussi une boîte avec un poids, ajustez si Occasion a un poids direct
                    $currentLineWeight = $docLine->getOccasion()->getBoite()->getWeigth();
                } elseif ($docLine->getItem() !== null) {
                    $currentLineWeight = $docLine->getItem()->getWeigth();
                }
                $donnees[$family]['totalWeigth'] += $currentLineWeight;
            }
        }

        // Ajout du taux de TVA
        $donnees['tauxTva'] = $this->utilitiesService->calculTauxTva($document->getTaxRateValue());

        // Calcul d'un sous-total général si nécessaire, combinant les trois familles
        // Ce n'est pas explicitement demandé mais peut être utile pour votre "Sous-total (HT)"
        $donnees['totalGeneralPriceExcludingTax'] = 
            $donnees['items']['totalPriceExcludingTax'] + 
            $donnees['occasions']['totalPriceExcludingTax'] + 
            $donnees['boites']['totalPriceExcludingTax'];

        return $donnees;
    }

    public function generateDocumentInDatabaseFromSaleDuringAfair($panierParams,$billingAddress,$deliveryAddress,$entityInstance)
    {

        //ON genere un nouveau numero de devis
        $quoteNumber = $this->generateNewNumberOf("quoteNumber", "getQuoteNumber");

        //on cherche les parametres des documents
        $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);

        //puis on met dans la base
        $document = new Document();
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        $endDevis = $now->add(new DateInterval('P'.$docParams->getDelayBeforeDeleteDevis().'D'));

        $document
                ->setToken($this->utilitiesService->generateRandomString())
                ->setQuoteNumber($docParams->getQuoteTag().$quoteNumber)
                ->setTotalExcludingTax($panierParams['totalPanier'])
                ->setDeliveryAddress($deliveryAddress)
                ->setUser($this->userRepository->findOneBy(['email' => 'client_de_passage@refaitesvosjeux.fr']))
                ->setBillingAddress($billingAddress)
                ->setTotalWithTax($this->utilitiesService->htToTTC($panierParams['totalPanier'],$panierParams['tax']->getValue()))
                ->setDeliveryPriceExcludingTax($panierParams['deliveryCostWithoutTax']->getPriceExcludingTax())
                ->setIsQuoteReminder(false)
                ->setIsLastQuote(true)
                ->setEndOfQuoteValidation($endDevis)
                ->setCreatedAt($now)
                ->setTaxRate($panierParams['tax'])
                ->setTaxRateValue($panierParams['tax']->getValue())
                ->setCost($panierParams['preparationHt'])
                ->setIsDeleteByUser(false)
                ->setTimeOfSendingQuote($now)
                ->setDocumentStatus($this->documentStatusRepository->findOneBy(['action' => 'SEND_END']))
                ->setShippingMethod($panierParams['shipping']);

        $this->em->persist($document);
        $this->em->flush();


        $documentLine = new DocumentLine();
        $documentLine
            ->setOccasion($panierParams['occasion']) //?cela met l'occasion hors ligne et comme vendu
            ->setQuantity(1)
            ->setDocument($document)
            ->setPriceExcludingTax($panierParams['totauxOccasions']['price']);
        $this->em->persist($documentLine);

        $this->em->flush();

        $paiement = $this->paymentRepository->findOneBy(['document' => $document]);

        if(!$paiement){
            $paiement = new Payment();
        }

        //on renseigne le paiement
        $paiement->setDocument($document)
                ->setMeansOfPayment($entityInstance->getMeansOfPaiement())
                ->setTokenPayment('AUCUN')
                ->setCreatedAt($now)
                ->setTimeOfTransaction($entityInstance->getMovementTime())
                ->setDetails('AUCUN DETAILS')
                ->setTimeOfTransaction($entityInstance->getMovementTime());
        //on sauvegarde le paiement
        $this->em->persist($paiement);
        $this->em->flush();

        //il faut creer le numero de facture
        $billNumber = $this->generateNewNumberOf('billNumber', 'getBillNumber');

        //on met a jour le document en BDD
        $etat = $this->documentStatusRepository->findOneBy(['action' => 'SEND_END']); //Envoyer
        $document->setDocumentStatus($etat)->setBillNumber($docParams->getBillingTag().$billNumber)->setSendingAt($now);
        $this->em->persist($document);
        $this->em->flush();

        return $document;
    }

    public function generatePdf(Document $document)
    {

        $results = $this->generateValuesForDocument($document);
        $legales = $this->legalInformationRepository->findOneBy([]);

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('chroot', realpath(''));
        $pdfOptions->isRemoteEnabled(true);

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        $html = $this->twig->render('pdf/document.html.twig', [
            'document' => $document,
            'legales' => $legales,
            'results' => $results
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        
        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("Facture RVJ - ".$document->getBillNumber().".pdf", [
            "Attachment" => false
        ]);
        exit(0);
    }

    public function statusChange(Document $document, DocumentStatus $status)
    {
        $legales = $this->legalInformationRepository->findOneBy([]);

        //pour toutes les actions qui contiennent END (DEPOSITE_COOP_END, SEND_END, etc)
        if(str_contains($status->getAction(),'END')){
            $now = new DateTimeImmutable('now');

            $document->setSendingAt($now)->setSendingNumber(NULL);
        }

        $document->setDocumentStatus($status);
        $this->em->persist($document);
        $this->em->flush();

        $this->mailService->sendMail(
            false,
            $document->getUser()->getEmail(),
            'Suivi de votre commande '.$document->getBillNumber(),
            'changement_statut',
            [
                'document' => $document,
                'legales' => $legales
            ],
            null,
            true
        );
    }

    public function renderIfDocumentNoExist() //TODO
    {
        $tableau = [
            'h1' => 'Document non trouvé !',
            'p1' => 'La consultation/ modification de ce document est impossible!',
            'p2' => 'Document inconnu ou supprimé !'
        ];

        return new Response($this->twig->render('site/document_view/_end_view.html.twig', [
            'tableau' => $tableau
        ]));
    }

    public function importDocuments(SymfonyStyle $io): void
    {
        $io->title('Importation des documents');

        $docs = $this->readCsvFileDocuments();
        
        $io->progressStart(count($docs));

        foreach($docs as $arrayDoc){
            $io->progressAdvance();
            $document= $this->createOrUpdateDocument($arrayDoc);

            $this->em->persist($document);
        }

        $this->em->flush();

        $io->progressFinish();
        unset($docs);
        $io->success('Importation terminée');
    }

    private function readCsvFileDocuments(): Reader
    {
        $csvDocuments = Reader::createFromPath('%kernel.root.dir%/../import/_table_documents.csv','r');
        $csvDocuments->setHeaderOffset(0);

        return $csvDocuments;
    }

    private function createOrUpdateDocument(array $arrayDoc): Document
    {
        $document = $this->documentRepository->findOneBy(['token' => $arrayDoc['validKey']]);

        if(!$document){
            $document = new Document();
        }

        $document
        ->setToken($arrayDoc['validKey'])
        ->setRvj2id($arrayDoc['idDocument'])
        ->setQuoteNumber($arrayDoc['numero_devis']);

        $numFacture = $arrayDoc['numero_facture'];
        if(is_null($this->utilitiesService->stringToNull($arrayDoc['numero_facture']))){
            $numFacture = null;
        }else{
            $numFacture = $arrayDoc['numero_facture'];
        }

        $document
            ->setBillNumber($numFacture)
            ->setTotalExcludingTax($arrayDoc['totalHT'])
            ->setTotalWithTax($arrayDoc['totalTTC'])
            ->setDeliveryPriceExcludingTax($arrayDoc['prix_expedition'])
            ->setBillingAddress($arrayDoc['adresse_facturation'])
            ->setDeliveryAddress($arrayDoc['adresse_livraison'])
            ->setIsQuoteReminder($arrayDoc['relance_devis'])
            ->setEndOfQuoteValidation($this->utilitiesService->getDateTimeImmutableFromTimestamp($arrayDoc['end_validation']))
            ->setCreatedAt($this->utilitiesService->getDateTimeImmutableFromTimestamp($arrayDoc['time']))
            ->setTimeOfSendingQuote($this->utilitiesService->getDateTimeImmutableFromTimestamp($arrayDoc['time_mail_devis']))
            ->setIsDeleteByUser(false)
            ->setMessage($this->utilitiesService->stringToNull($arrayDoc['commentaire']))
            ->setTaxRate($this->taxRepository->findOneBy(['value' => 0]))
            ->setTaxRateValue(0)
            ->setCost($arrayDoc['prix_preparation'])
            ->setTokenPayment($this->utilitiesService->stringToNull($arrayDoc['num_transaction']));

        //?ok version 3
        $envoyer = explode('|',$arrayDoc['envoyer']);

        if($arrayDoc['etat'] == 2 && count($envoyer) > 1){
            $etat = $this->documentStatusRepository->findOneBy(['name' => $_ENV['DOCUMENT_STATUS_END']]);
        }else if($arrayDoc['etat'] == 3){ // 3 = envoyé dans la version 2
            $etat = $this->documentStatusRepository->findOneBy(['name' => $_ENV['DOCUMENT_STATUS_END']]); 
        }else if($arrayDoc['etat'] == 2 && $arrayDoc['envoyer'] == 0){
            $etat = $this->documentStatusRepository->findOneBy(['name' => $_ENV['DOCUMENT_STATUS_PAID_TO_PREPARE']]); 
        }else if($arrayDoc['etat'] == 1){ // non facturer dans version 2
            $etat = $this->documentStatusRepository->findOneBy(['name' => $_ENV['DOCUMENT_STATUS_NO_PAID']]);
        }else{
            $etat = $this->documentStatusRepository->findOneBy(['name' => $_ENV['DOCUMENT_STATUS_INDEFINED']]);
        }
        $document->setDocumentStatus($etat);

        //?ok version 3
        $user = $this->userRepository->findOneBy(['rvj2id' => (int) $arrayDoc['idUser']]);
        if(!$user){
            $document->setUser($this->userRepository->findOneBy(['email' => $_ENV['UNDEFINED_USER_EMAIL']]));
        }else{
            $document->setUser($user);
        }

        return $document;
    }

    public function creationDocumentSending(SymfonyStyle $io): void
    {
        $io->title('Création des envois');

        $docs = $this->readCsvFileDocuments();
        
        $io->progressStart(count($docs));

        foreach($docs as $arrayDoc){
            $io->progressAdvance();
            $document = $this->createOrUpdateDocumentSending($arrayDoc);

            $this->em->persist($document);
        }

        $this->em->flush();

        $io->progressFinish();
        unset($docs);
        $io->success('Création terminée');
    }

    private function createOrUpdateDocumentSending(array $arrayDoc):Document
    {
        $document = $this->documentRepository->findOneBy(['rvj2id' => $arrayDoc['idDocument']]);

        //?ok version 3
        if($arrayDoc['expedition'] == "poste"){
            $expedition = $this->shippingMethodRepository->findOneBy(['name' => $_ENV['SHIPPING_METHOD_BY_POSTE_NAME']]);
        }else if($arrayDoc['expedition'] == "mondialRelay"){
            $expedition = $this->shippingMethodRepository->findOneBy(['name' => $_ENV['SHIPPING_METHOD_BY_MONDIAL_RELAY_NAME']]);
        }else if($arrayDoc['expedition'] == "retrait_caen1"){
            $expedition = $this->shippingMethodRepository->findOneBy(['name' => $_ENV['SHIPPING_METHOD_BY_IN_RVJ_DEPOT_NAME']]);
        }else if($arrayDoc['expedition'] == "colissimo"){
            $expedition = $this->shippingMethodRepository->findOneBy(['name' => $_ENV['SHIPPING_METHOD_BY_COLISSIMO_NAME']]);
        }else{
            $expedition = $this->shippingMethodRepository->findOneBy(['name' => $_ENV['SHIPPING_METHOD_BY_INDEFINED']]);
        }

        $envoyer = explode('|',$arrayDoc['envoyer']);

        if(count($envoyer) > 1){
            
            $timeSending = $this->utilitiesService->getDateTimeImmutableFromTimestamp($envoyer[0]);
            
        }else{
            
            $timeSending = NULL;
        }

        if(count($envoyer) > 1){

            if($envoyer[1] == "SANS"){

                $sendingNumber = NULL;

            }else{

                $sendingNumber = $envoyer[1];
            }

        }else{

            $sendingNumber = NULL;
        }

        $document->setShippingMethod($expedition)->setSendingAt($timeSending)->setSendingNumber($sendingNumber);

        return $document;
    }

    public function reminderQuotes(DateTimeImmutable $now)
    {

        $documents = $this->documentRepository->findByDevisToReminder($now);
        //on cherche les parametres des documents
        $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);
        $legales = $this->legalInformationRepository->findOneBy([]);

        foreach($documents as $document){

            if($document->getQuoteRequest() != null){
                $endDevis = $now->add(new DateInterval('P'.$docParams->getDelayBeforeDeleteQuoteRequest().'D'));
            }else{
                $endDevis = $now->add(new DateInterval('P'.$docParams->getDelayBeforeDeleteDevis().'D'));
            }
            $document->setEndOfQuoteValidation($endDevis)->setIsQuoteReminder(true);
            $this->em->persist($document);

            $this->mailService->sendMail(
                false,
                $document->getUser()->getEmail(),
                'Devis '.$document->getQuoteNumber().' en attente...',
                'reminder_quote',
                [
                    'document' => $document,
                    'endDevis' => $document->getEndOfQuoteValidation(),
                    'docParams' => $docParams,
                    'legales' => $legales
                ],
                null,
                false
            );

        }

        $this->em->flush();
    }

    public function generateDocumentInDatabaseFromReserve(Reserve $reserve, array $allPricesHtFromRequest, Address $billingAddressFromForm, $deliveryAddressFromForm, ShippingMethod $shippingMethodFromForm)
    {        
        $details = $this->requestStack->getSession()->get('detailsForManualInvoice');
        $user = $this->security->getUser();
        $dateOfTransaction = new DateTimeImmutable($details['transactionDate'], new DateTimeZone('Europe/Paris'));
        $meanOfPaiement = $this->meansOfPayementRepository->findOneBy(['id' => $details['paiementId']]);
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        
        //on genere des paniers pour chaque occasions
        $paniers = [];
        foreach($reserve->getOccasions() as $key =>$occasion){
            $panier = new Panier();
            $panier->setOccasion($occasion);
            $panier->setQte(1);
            $panier->setPriceWithoutTax($allPricesHtFromRequest[$key] * 100);
            $panier->setUnitPriceExclusingTax($allPricesHtFromRequest[$key] * 100);
            $panier->setUser($reserve->getUser());
            $panier->setCreatedAt($now);
            $this->em->persist($panier);
            $paniers[] = $panier;
        }
 
        //totaux du panier
        $totalHtPanierInCents = 0;
        $totalWeightPanier = 0;
        foreach($paniers as $panier){
            $totalHtPanierInCents += $panier->getPriceWithoutTax();
            $totalWeightPanier += $panier->getOccasion()->getBoite()->getWeigth();
        }


        //recherche du prix de la livraison
        $deliveryCostInCents = 0;
        $delivery = $this->deliveryRepository->findCostByDeliveryShippingMethod($shippingMethodFromForm, $totalWeightPanier, $user);
        if($delivery == null){
            $france = $this->countryRepository->findOneBy(['name' => 'FRANCE']);
            $user = new User();
            $user->setCountry($france);
            $delivery = $this->deliveryRepository->findCostByDeliveryShippingMethod($shippingMethodFromForm, $totalWeightPanier, $user);
        }
        $deliveryCostInCents = $delivery->getPriceExcludingTax();

        
        $entityInstance = new OffSiteOccasionSale();
        $entityInstance->setPlaceOfTransaction('Vente emportée')->setMovementTime($now);

        $details = [];
        $details['panier_boites'] = ['init' => 'init']; // for action in admin

        //frais de preparations car on veut que des occasions
        $preparationHt = 0;

        $reponses['totauxOccasions'] = [];
        $reponses['totauxOccasions'] = $this->utilitiesService->totauxByPanierGroup($paniers);
        $details['panier_occasions'] = [];
        $details['panier_boites'] = [];
        $details['panier_items'] = [];
        $tax = $this->taxRepository->findOneBy([]);
        $taxValue = $tax->getValue();



        //ON genere un nouveau numero de devis
        $quoteNumber = $this->generateNewNumberOf("quoteNumber", "getQuoteNumber");

        //on cherche les parametres des documents
        $docParams = $this->documentParametreRepository->findOneBy(['isOnline' => true]);

        //puis on met dans la base
        $document = new Document();
        $endDevis = $now->add(new DateInterval('P'.$docParams->getDelayBeforeDeleteDevis().'D'));

        $document
                ->setToken($this->utilitiesService->generateRandomString())
                ->setQuoteNumber($docParams->getQuoteTag().$quoteNumber)
                ->setTotalExcludingTax($totalHtPanierInCents)
                ->setDeliveryAddress($deliveryAddressFromForm)
                ->setUser($reserve->getUser())
                ->setBillingAddress($billingAddressFromForm)
                ->setTotalWithTax($this->utilitiesService->htToTTC($totalHtPanierInCents + $deliveryCostInCents, $taxValue))
                ->setDeliveryPriceExcludingTax($deliveryCostInCents)
                ->setIsQuoteReminder(false)
                ->setIsLastQuote(true)
                ->setEndOfQuoteValidation($endDevis)
                ->setCreatedAt($now)
                ->setTaxRate($tax)
                ->setTaxRateValue($taxValue)
                ->setCost($preparationHt)
                ->setIsDeleteByUser(false)
                ->setTimeOfSendingQuote($now)
                ->setDocumentStatus($this->documentStatusRepository->findOneBy(['action' => 'SEND_END']))
                ->setShippingMethod($shippingMethodFromForm);

        $this->em->persist($document);
        $this->em->flush();

        //on genere les lignes du document
        foreach($paniers as $panier){
            $documentLine = new DocumentLine();
            $documentLine
                ->setOccasion($occasion)
                ->setQuantity(1)
                ->setDocument($document)
                ->setPriceExcludingTax($panier->getPriceWithoutTax());
            $this->em->persist($documentLine);
        }
        $this->em->flush();


        $paiement = $this->paymentRepository->findOneBy(['document' => $document]);

        if(!$paiement){
            $paiement = new Payment();
        }

        //on renseigne le paiement
        $paiement->setDocument($document)
                ->setMeansOfPayment($meanOfPaiement)
                ->setTokenPayment('AUCUN')
                ->setCreatedAt($now)
                ->setTimeOfTransaction($dateOfTransaction)
                ->setDetails('AUCUN DETAILS');
        //on sauvegarde le paiement
        $this->em->persist($paiement);
        $this->em->flush();

        //il faut creer le numero de facture
        $billNumber = $this->generateNewNumberOf('billNumber', 'getBillNumber');

        //on met a jour le document en BDD
        if($deliveryAddressFromForm instanceof EntityCollectionPoint){

            $etat = $this->documentStatusRepository->findOneById($deliveryAddressFromForm->getDocumentStatus());//deposer à la coop

        }else{
            
            $etat = $this->documentStatusRepository->findOneBy(['action' => 'SEND_END']); //Envoyer
        }
        $document->setDocumentStatus($etat)->setBillNumber($docParams->getBillingTag().$billNumber)->setSendingAt($dateOfTransaction);
        $this->em->persist($document);
        $this->em->flush();

        //on vide la reserve
        foreach($reserve->getOccasions() as $occasion){
            $reserve->removeOccasionAfterBillingReserve($occasion);
            $this->em->persist($reserve);
        }
        //on supprime la reserve
        $this->em->remove($reserve);

        $this->em->flush();

        //on supprime la session
        $this->requestStack->getSession()->remove('detailsForManualInvoice');

        return $document;
    }
}