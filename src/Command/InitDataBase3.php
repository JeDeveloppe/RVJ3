<?php

namespace App\Command;

use App\Service\AdresseService;
use App\Service\BoiteService;
use App\Service\CollectionPointService;
use App\Service\ColorService;
use App\Service\DeliveryService;
use App\Service\DiscountService;
use App\Service\DocumentLigneService;
use App\Service\DocumentService;
use App\Service\DocumentStatusService;
use App\Service\DurationOfGameService;
use App\Service\EditorService;
use App\Service\EnvelopeService;
use App\Service\ShippingMethodService;
use App\Service\ItemGroupService;
use App\Service\LegalInformationService;
use App\Service\MeansOffPayementService;
use App\Service\MediaService as ServiceMediaService;
use App\Service\OccasionService;
use App\Service\OffSiteOccasionSaleService;
use App\Service\PaiementService;
use App\Service\PartnerService;
use App\Service\PlayerService;
use App\Service\SiteSettingsService;
use App\Service\StockService;
use App\Service\UserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:initdatabase3')]

class InitDataBase3 extends Command
{
    public function __construct(
            private AdresseService $adresseService,
            private BoiteService $boiteService,
            private OffSiteOccasionSaleService $offSiteOccasionSaleService,
            private MeansOffPayementService $meansOffPayementService,
            private ShippingMethodService $shippingMethodService,
            private DocumentLigneService $documentLigneService,
            private DocumentService $documentService,
            private EditorService $editorService,
            private LegalInformationService $legalInformationService,
            private ServiceMediaService $mediaService,
            private OccasionService $occasionService,
            private PlayerService $playerService,
            private SiteSettingsService $siteSettingsService,
            private ItemGroupService $itemGroupService,
            private PartnerService $partnerService,
            private PaiementService $paiementService,
            private DocumentStatusService $documentStatusService,
            private EnvelopeService $envelopeService,
            private ColorService $colorService,
            private DeliveryService $deliveryService,
            private DiscountService $discountService,
            private UserService $userService,
            private CollectionPointService $collectionPointService,
            private DurationOfGameService $durationOfGameService,
            private StockService $stockService
        )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        // ini_set('memory_limit', '2048M');
        ini_set("memory_limit", -1);

        $io = new SymfonyStyle($input,$output);

        //on importe les partenaires
        $this->partnerService->importPartenaires($io);

        //on importe les adresses (facturation et livraison)
        $this->adresseService->importAdresses($io);

        //on creer le nombre de joueurs
        $this->playerService->addplayers($io);

        //on cree les temps de jeux
        $this->durationOfGameService->addDurations($io);

        //on importe les boites
        // $this->boiteService->importBoites($io);
        $this->boiteService->alignementBoitesRVJ3($io);


        //SI ON EST EN DEV on génére de facon aléatoire la duree des parties aux boites
        if($_ENV['APP_ENV'] == 'dev' or $_ENV['APP_ENV'] == 'DEV'){
            $this->durationOfGameService->addAleatoireDurationsToBoites($io);
        }

        //on genere les editeurs de facon distinct
        $this->editorService->addEditorsInDatabase($io);

        //on importe le detail des boites
        $this->boiteService->importPieces($io);

        //on cree utilisateur undefini, adresse de retrait COOP, methodes de retrait
        $this->userService->createUndefinedUser($io);
        $this->boiteService->createUndefinedBoite($io);
        $this->adresseService->createRetredAddress($io);
        $this->shippingMethodService->createShippingMethode($io);
        $this->collectionPointService->addCollectionPoint($io);

        //on cree les conditions des occasions
        $this->occasionService->addConditions($io);

        //on cree les MOYENS DE PAIEMENT
        $this->meansOffPayementService->addMoyens($io);

        //on importe les stocks du site
        $this->stockService->addStocks($io);
        //on importe les jeux complet
        $this->occasionService->importOccasions($io);

        //on cree les mouvements des occasions
        $this->offSiteOccasionSaleService->importMouvementsOccasions($io);

        //on met a jour les occasions avec les mouvements
        $this->occasionService->updateOccasionMouvement($io);

        //on crer les information legale et la tax
        $this->legalInformationService->creationLegalInformation($io);

        //on cree les status des documents
        $this->documentStatusService->creationStatus($io);

        //on importe les documents et les paiements
        $this->documentService->importDocuments($io);
        $this->paiementService->importPaiements($io);
        $this->documentService->creationDocumentSending($io);

        //on importe les lignes de chaque document
        $this->documentLigneService->importDocumentsLigneBoites($io);
        $this->documentLigneService->importDocumentsLigneOccasion($io);

        //on cree les enveloppes et les couleurs pour les articles, les enveloppes, les joueurs, les livraisons
        $this->deliveryService->addDelivery();
        $this->envelopeService->addEnvelopes($io);
        $this->colorService->addColors($io);
        $this->discountService->addDiscounts($io);

        //on cree les settings du site
        $this->siteSettingsService->addSettings($io);

        //on injecte les medias
        $this->mediaService->addBadgeForMedia();
        $this->mediaService->importMedias($io);

        //on injecte les groups pour les items
        $this->itemGroupService->addItemGroups($io);

        return Command::SUCCESS;
    }

}