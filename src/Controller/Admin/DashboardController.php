<?php

namespace App\Controller\Admin;

use DateTimeImmutable;
use App\Entity\Occasion;
use App\Service\MailService;
use App\Service\AdminService;
use App\Service\PanierService;
use App\Service\DocumentService;
use App\Service\PaiementService;
use App\Repository\ItemRepository;
use App\Repository\UserRepository;
use App\Repository\PanierRepository;
use App\Repository\PaymentRepository;
use App\Repository\ReserveRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SiteSettingRepository;
use App\Repository\QuoteRequestRepository;
use App\Repository\ResetPasswordRepository;
use App\Repository\DocumentStatusRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\OffSiteOccasionSaleRepository;
use App\Repository\ReturndetailstostockRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use App\Controller\Admin\EasyAdmin\AddressCrudController;
use App\Controller\Admin\EasyAdmin\AmbassadorCrudController;
use App\Controller\Admin\EasyAdmin\BadgeForMediaTimelineCrudController;
use App\Controller\Admin\EasyAdmin\BoiteCrudController;
use App\Controller\Admin\EasyAdmin\CatalogOccasionSearchCrudController;
use App\Controller\Admin\EasyAdmin\CityCrudController;
use App\Controller\Admin\EasyAdmin\CollectionPointCrudController;
use App\Controller\Admin\EasyAdmin\ConditionOccasionCrudController;
use App\Controller\Admin\EasyAdmin\CountryCrudController;
use App\Controller\Admin\EasyAdmin\DeliveryCrudController;
use App\Controller\Admin\EasyAdmin\DepartmentCrudController;
use App\Controller\Admin\EasyAdmin\DiscountCrudController;
use App\Controller\Admin\EasyAdmin\DocumentCrudController;
use App\Controller\Admin\EasyAdmin\DocumentParametreCrudController;
use App\Controller\Admin\EasyAdmin\DocumentStatusCrudController;
use App\Controller\Admin\EasyAdmin\DurationOfGameCrudController;
use App\Controller\Admin\EasyAdmin\EditorCrudController;
use App\Controller\Admin\EasyAdmin\EnvelopeCrudController;
use App\Controller\Admin\EasyAdmin\GranderegionCrudController;
use App\Controller\Admin\EasyAdmin\ItemCrudController;
use App\Controller\Admin\EasyAdmin\ItemGroupCrudController;
use App\Controller\Admin\EasyAdmin\LegalInformationCrudController;
use App\Controller\Admin\EasyAdmin\LevelCrudController;
use App\Controller\Admin\EasyAdmin\MeansOfPayementCrudController;
use App\Controller\Admin\EasyAdmin\MediaCrudController;
use App\Controller\Admin\EasyAdmin\MovementOccasionCrudController;
use App\Controller\Admin\EasyAdmin\NumbersOfPlayersCrudController;
use App\Controller\Admin\EasyAdmin\OccasionCrudController;
use App\Controller\Admin\EasyAdmin\OffSiteOccasionSaleCrudController;
use App\Controller\Admin\EasyAdmin\PanierCrudController;
use App\Controller\Admin\EasyAdmin\PartnerCrudController;
use App\Controller\Admin\EasyAdmin\PaymentCrudController;
use App\Controller\Admin\EasyAdmin\QuoteRequestCrudController;
use App\Controller\Admin\EasyAdmin\ReserveCrudController;
use App\Controller\Admin\EasyAdmin\ResetPasswordCrudController;
use App\Controller\Admin\EasyAdmin\ReturndetailstostockCrudController;
use App\Controller\Admin\EasyAdmin\SearchBoiteLogCrudController;
use App\Controller\Admin\EasyAdmin\ShippingMethodCrudController;
use App\Controller\Admin\EasyAdmin\SiteSettingCrudController;
use App\Controller\Admin\EasyAdmin\StockCrudController;
use App\Controller\Admin\EasyAdmin\StoreCrudController;
use App\Controller\Admin\EasyAdmin\TaxCrudController;
use App\Controller\Admin\EasyAdmin\UserCrudController;
use App\Controller\Admin\EasyAdmin\VoucherDiscountCrudController;


#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private OffSiteOccasionSaleRepository $offSiteOccasionSaleRepository,
        private PaymentRepository $paymentRepository,
        private DocumentRepository $documentRepository,
        private DocumentStatusRepository $documentStatusRepository,
        private ItemRepository $itemRepository,
        private DocumentService $documentService,
        private ResetPasswordRepository $resetPasswordRepository,
        private SiteSettingRepository $siteSettingRepository,
        private MailService $mailService,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private PaiementService $paiementService,
        private ReserveRepository $reserveRepository,
        private PanierRepository $panierRepository,
        private PanierService $panierService,
        private AdminService $adminService,
        private QuoteRequestRepository $quoteRequestRepository,
        private ReturndetailstostockRepository $returndetailstostockRepository
    )
    {
        
    }
    
    public function index(): Response
    {

        $now = new DateTimeImmutable('now');
        $setting = $this->siteSettingRepository->findOneBy([]);

        //?réconciliation des paiements HelloAsso non remontés (doit passer avant la suppression des devis expirés)
        if($_ENV['PAIEMENT_MODULE'] == "HELLOASSO"){
            $this->paiementService->verifyHelloAssoPayments();
        }

        //?remise en stock des items / boite supérieur à X jours dans les devis non payés
        $this->documentService->deleteDocumentFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

        //?relance email des devis
        $this->documentService->reminderQuotes($now);

        //?suppression des paniers > x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

        //?on compte le nombre d'items sans stock
        $itemsWithStockIsNull = $this->itemRepository->findByStockForSaleIsNull();

        $documentsEnAttenteDePaiement = $this->documentRepository->findBy(['billNumber' => NULL, 'isLastQuote' => false]);
        $detailsDesVentesEnAttenteDePaiement = [];

        $occasions = 0;
        $items = 0;

        foreach($documentsEnAttenteDePaiement as $document){
            foreach($document->getDocumentLines() as $line){
                if($line->getOccasion()){
                    $occasions += 1;
                }
                if($line->getItem()){
                    $items += 1;
                }
            }
        }

        $detailsDesVentesEnAttenteDePaiement[] = ['name' => 'Occasion(s)', 'valeur' => $occasions];
        $detailsDesVentesEnAttenteDePaiement[] = ['name' => 'Article(s)', 'valeur' => $items];


        //?on compte le nombre d'inscrits
        $numberOfclients = $this->userRepository->countUsers();

        //?on compte le chiffre d'affaire
        $totalPayment = $this->documentRepository->countSumOfAllDocumentsWhenDocumentIsPayed();

        //?on compte le chiffre hors du site
        $totalOccasionSale = $this->offSiteOccasionSaleRepository->countSumOfAllOccasionSales();


        $totals[] = [
            'name' => 'CA - Ventes sur le site',
            'total' => $totalPayment,
            'isMoney' => true
        ];
        $totals[] = [
            'name' => 'CA - Ventes hors du site',
            'total' => $totalOccasionSale,
            'isMoney' => true
            
        ];

        $totals[] = [
            'name' => 'Nombre d\'inscrits sur le site:',
            'total' => $numberOfclients,
            'isMoney' => false
        ];

        return $this->render('admin/dashboard.html.twig', [
            'totals' => $totals,
            'itemsWithStockIsNull' => $itemsWithStockIsNull,
            'setting' => $setting,
            'detailsDesVentesEnAttenteDePaiement' => $detailsDesVentesEnAttenteDePaiement,
            'documentsEnAttenteDePaiement' => count($documentsEnAttenteDePaiement)
        ]);
    }

    #[Route('/admin/traitement-quotidien/commandes', name: 'admin_traited_daily_commands')]
    public function commandesTraitedDaily(): Response
    {
        $datas = [];
        $status = [];
        $statusToBeTraitedDailys = $this->documentStatusRepository->findStatusIsTraitedDaily();
        $documentstatus = $this->documentStatusRepository->findAll();
        $setting = $this->siteSettingRepository->findOneBy([]);

        foreach($documentstatus as $documentStatus){
            $status[$documentStatus->getAction()] = $documentStatus->getAction();
        }


        foreach($statusToBeTraitedDailys as $statusToBeTraitedDaily){

            $datas[$statusToBeTraitedDaily->getAction()] = 
                [
                    'value' => $statusToBeTraitedDaily->getName(),
                    'action' => $statusToBeTraitedDaily->getAction(),
                    'documents' => $this->documentRepository->findDocumentsToBeTraitedDailyWithStatus($statusToBeTraitedDaily)
                ];
        }

        return $this->render('admin/traited_daily_commands.html.twig', [
            'datas' => $datas,
            'status' => $status,
            'documentsStatus' => $documentstatus,
            'setting' => $setting
        ]);
    }

    #[Route('/admin/traitement-quotidien/devis', name: 'admin_traited_daily_devis')]
    public function devisTraitedDaily(): Response
    {

        // $entityDevisWithPrice = $this->documentStatusRepository->findOneBy(['action' => $_ENV['DEVIS_NO_PAID_LABEL']]);
        $datas = $this->documentRepository->findBy(['billNumber' => NULL, 'isDeleteByUser' => false]);

        return $this->render('admin/traited_daily_devis.html.twig', [
            'datas' => $datas,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('RVJ3')->setFaviconPath('/build/images/favicon/favicon.ico');
    }

    public function configureMenuItems(): iterable
    {

        $statusToBeTraitedDailys = $this->documentStatusRepository->findStatusIsTraitedDaily();
        $commandBadges = [];
        foreach($statusToBeTraitedDailys as $statusToBeTraitedDaily){
            $commandBadges[] = count($this->documentRepository->findDocumentsToBeTraitedDailyWithStatus($statusToBeTraitedDaily));
        }
        $cartsCount = $this->panierRepository->countActiveCarts();
        $waitingToBePaid = count($this->documentRepository->findBy(['billNumber' => NULL, 'isDeleteByUser' => false ]));
        $reservesCount = $this->reserveRepository->countReserves();
        $devisCount = $this->quoteRequestRepository->countQuoteRequestWhoMustByTraited();
        $returnStock = $this->returndetailstostockRepository->countReturnStockWhereMustBeTraited();
        
        yield MenuItem::linkToDashboard('Dashboard ADMIN', 'fa fa-home');        
        yield MenuItem::linkToRoute('SITE','fa-solid fa-earth-europe','app_home');
        yield MenuItem::linkToUrl('Messageries Ionos','fa-solid fa-envelope','https://id.ionos.fr/identifier')->setLinkTarget('_blank')->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Traitements quotidien:')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(ReturndetailstostockCrudController::class, 'RETOUR EN STOCK', 'fa-solid fa-rotate-left')->setPermission('ROLE_ADMIN')
            ->setBadge($returnStock,'primary');
        yield MenuItem::linkToRoute('COMMANDES','fa-solid fa-money-bill','admin_traited_daily_commands')->setPermission('ROLE_ADMIN')
            ->setBadge(array_sum($commandBadges),'success');
        yield MenuItem::linkTo(QuoteRequestCrudController::class, 'DEMANDE DE DEVIS', 'fa-solid fa-list')->setPermission('ROLE_ADMIN')
            ->setBadge($devisCount,'success');
        yield MenuItem::linkToRoute('EN ATTENTE DE PAIEMENT','fa-solid fa-money-bill','admin_traited_daily_devis')->setPermission('ROLE_ADMIN')
            ->setBadge($waitingToBePaid,'success');
        yield MenuItem::linkToRoute('GRAPHIQUES','fa-solid fa-chart-simple','jpgraph')->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Gestion des boites:')->setPermission('ROLE_BENEVOLE');
        yield MenuItem::linkTo(BoiteCrudController::class, 'Boites', 'fas fa-list')->setPermission('ROLE_BENEVOLE');
        yield MenuItem::linkTo(EditorCrudController::class, 'Liste des éditeurs', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(NumbersOfPlayersCrudController::class, 'Liste des joueurs', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(DurationOfGameCrudController::class, 'Liste des durées des parties', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        
        yield MenuItem::section('Gestion des occasions:')->setPermission('ROLE_BENEVOLE');
        yield MenuItem::linkTo(OccasionCrudController::class, 'Occasions', 'fas fa-list')->setPermission('ROLE_BENEVOLE');
        yield MenuItem::linkTo(OffSiteOccasionSaleCrudController::class, 'Vente / don rapide', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(ReserveCrudController::class, 'RESERVER DES OCCASIONS', 'fa-solid fa-hand')->setPermission('ROLE_ADMIN')->setBadge($reservesCount,'info');
        yield MenuItem::linkTo(MovementOccasionCrudController::class, 'Types de mouvement', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(ConditionOccasionCrudController::class, 'Liste des états (pièces, boite, règle)', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(StockCrudController::class, 'Gestion stocks', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(CatalogOccasionSearchCrudController::class, 'Liste des recherches', 'fa-solid fa-magnifying-glass')->setPermission('ROLE_ADMIN');
        
        yield MenuItem::section('Gestion des documents:')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(DocumentCrudController::class, 'Les documents', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(PaymentCrudController::class, 'Liste des paiements', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(DocumentStatusCrudController::class, 'Status des documents', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(DocumentParametreCrudController::class, 'Paramètres des documents', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        
        yield MenuItem::section('Gestion des utilisateurs:')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(UserCrudController::class, 'Liste des clients', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(AddressCrudController::class, 'Liste des adresses', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(LevelCrudController::class, 'Liste des roles', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(ResetPasswordCrudController::class, 'Chgmts de mdp', 'fas fa-list')->setBadge(count($this->resetPasswordRepository->findBy(['isUsed' => false])),'info')->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Gestion des ambassadeurs')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(AmbassadorCrudController::class, 'Liste des ambassadeurs', 'fas fa-list')->setPermission('ROLE_ADMIN');
        
        yield MenuItem::section('Gestion des partenaires')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(PartnerCrudController::class, 'Liste des partenaires', 'fas fa-list')->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Gestion des articles:')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(ItemGroupCrudController::class, 'Groupe d\'articles', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(ItemCrudController::class, 'Articles', 'fas fa-list')->setPermission('ROLE_ADMIN');
        // yield MenuItem::linkTo(ColorCrudController::class, 'Couleurs', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(EnvelopeCrudController::class, 'Enveloppes', 'fas fa-list')->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Gestion des paniers:')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(PanierCrudController::class, 'Paniers en cours', 'fas fa-list')->setPermission('ROLE_ADMIN')->setBadge($cartsCount,'success');
        yield MenuItem::linkTo(ShippingMethodCrudController::class, 'Moyens de retrait/envoi', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(CollectionPointCrudController::class, 'Lieux de retrait', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(VoucherDiscountCrudController::class, 'Bon d\'achat', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(MeansOfPayementCrudController::class, 'Moyens de paiement', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(DeliveryCrudController::class, 'Prix des livraisons', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(DiscountCrudController::class, 'Remises de qté', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');


        yield MenuItem::section('Gestion des médias')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(MediaCrudController::class, 'Liste des médias', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(BadgeForMediaTimelineCrudController::class, 'Paramètre des badges', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Paramètres géographiques:')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(CityCrudController::class, 'Villes', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(DepartmentCrudController::class, 'Departements', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(GranderegionCrudController::class, 'Grandes région', 'fas fa-list')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(CountryCrudController::class, 'Pays', 'fas fa-list')->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Paramètres du site:')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(LegalInformationCrudController::class, 'Infos légales', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(TaxCrudController::class, 'Taxes', 'fa-solid fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(SiteSettingCrudController::class, 'Vacances, foires, etc...', 'fas fa-gear')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(SearchBoiteLogCrudController::class, 'Recherches dans le catalogue', 'fa-solid fa-magnifying-glass')->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Gestion boutique:')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(StoreCrudController::class, 'Magasins', 'fas fa-list')->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Mises à jour:')->setPermission('ROLE_SUPER_ADMIN');
        yield MenuItem::linkToRoute('Occasions','fa-solid fa-arrows-rotate','admin_update_occasions_billed')->setPermission('ROLE_SUPER_ADMIN');

    }

    #[Route('/admin/update-database/occasions/', name: 'admin_update_occasions_billed', methods: ['GET'])]
    public function updateOccasionsInDatabase(){

        $this->adminService->updateOccasionsLogic();

        $this->addFlash('success', 'Tous les occasions ont été mis à jour !');

        return $this->redirectToRoute('admin');
    }

}
