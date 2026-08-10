<?php

namespace App\Controller\Site;

use App\Entity\SearchBoiteLog;
use App\Service\PanierService;
use App\Repository\TaxRepository;
use App\Repository\BoiteRepository;
use App\Repository\EditorRepository;
use App\Form\SearchBoiteInCatalogueType;
use App\Repository\SiteSettingRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ItemRepository;
use App\Repository\SearchBoiteLogRepository;
use App\Repository\ShippingMethodRepository;
use App\Repository\CountryRepository;
use App\Repository\DeliveryRepository;
use App\Service\CatalogueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CatalogController extends AbstractController
{
    public function __construct(
        private BoiteRepository $boiteRepository,
        private PaginatorInterface $paginator,
        private EditorRepository $editorRepository,
        private TaxRepository $taxRepository,
        private PanierService $panierService,
        private SiteSettingRepository $siteSettingRepository,
        private CatalogueService $catalogueService,
        private ItemRepository $itemRepository,
        private ShippingMethodRepository $shippingMethodRepository,
        private CountryRepository $countryRepository,
        private DeliveryRepository $deliveryRepository,
    )
    {
    }

    #[Route('/catalogue-pieces-detachees', name: 'app_catalogue_pieces_detachees')]
    public function cataloguePiecesDetachees(Request $request, SearchBoiteLogRepository $searchBoiteLogRepository, EntityManagerInterface $em): Response
    {

        //?on supprimer les paniers de plus de x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();
        $siteSetting = $this->siteSettingRepository->findOneBy([]);
        $orderColumn = $request->query->get('orderColumn') ?? NULL;
        $activeTriWhereThereIsNoSearch = true;

        //?methode GET pour que la recherche apparaisse dans l'URL (permet au bouton "Retour au catalogue" de la fiche boite de la restaurer via le referer)
        $form = $this->createForm(SearchBoiteInCatalogueType::class, null, ['method' => 'GET']);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $activeTriWhereThereIsNoSearch = false;
            $search = $form->get('search')->getData();
            $donneesFromDatabases = $this->boiteRepository->findBoitesWhereThereIsItems($search);

            // --- ENREGISTREMENT DU LOG ---
            if ($search) {
                $log = new SearchBoiteLog();
                $log->setQuery(mb_strtolower(trim($search)));
                $log->setCreatedAt(new \DateTimeImmutable());
                $log->setResultsCount(count($donneesFromDatabases));

                $em->persist($log);
                $em->flush();

                // On lance le nettoyage automatique
                $searchBoiteLogRepository->deleteOldLogs(500);
            }

        }else{

            //si on cherche par ordre des noms de boite
            if($orderColumn == 'name'){

                $items = $this->itemRepository->findAllItemsWithStockForSaleNotNullAndBoiteOrigine();

            }else{

                //si on cherche par ordre des derniers articles ajoutés
                $items = $this->itemRepository->findAllItemsWithStockForSaleNotNullOrderByUpdatedAtDescAndBoiteOrigine();
            }

            $donneesFromDatabases = [];
            foreach($items as $item){
                $boites = $item->getBoiteOrigine();
                foreach($boites as $boite){
                    if(!in_array($boite, $donneesFromDatabases)){
                        $donneesFromDatabases[] = $boite;
                    }
                }
            }

            //si on cherche par ordre des noms de boite
            if($orderColumn == 'name'){
                usort($donneesFromDatabases, function($a, $b) {
                    return strcmp($a->getName(), $b->getName());
                });
            }
        }

        $donnees = $this->catalogueService->addNumberOfItemWithStockNotNull($donneesFromDatabases);

        $boites = $this->paginator->paginate(
            $donnees, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            12 /*limit per page*/
        );


        $metas['description'] = 'Catalogue complet de toutes les boites dont le service dispose de pièces détachées.';

        return $this->render('site/pages/catalog/pieces_detachees/pieces_detachees.html.twig', [
            'boites' => $boites,
            'form' => $form,
            'search' => $search ?? null,
            'activeTriWhereThereIsNoSearch' => $activeTriWhereThereIsNoSearch,
            'forStructure' => false,
            'metas' => $metas,
            'totalPiecesDisponiblentSurLeSite' => count($this->itemRepository->findAllItemsWithStockForSaleNotNull()),
            'tax' => $this->taxRepository->findOneBy([]),
            'siteSetting' => $siteSetting
        ]);
    }

    #[Route('/catalogue-pieces-detachees/{id}/{editorSlug}/{boiteSlug}/', name: 'catalogue_pieces_detachees_articles_d_une_boite', requirements: ['boiteSlug' => '[a-z0-9\-]+'] )]
    public function cataloguePiecesDetacheesArticlesDuneBoite($id, $editorSlug, $boiteSlug, $year = NULL, $search = NULL): Response
    {
        //?on supprimer les paniers de plus de x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

        $boite = $this->boiteRepository->findOneBy(['id' => $id, 'slug' => $boiteSlug, 'editor' => $this->editorRepository->findOneBy(['slug' => $editorSlug]), 'isOnline' => true]);

        if(!$boite){
            $this->addFlash('warning', 'Boite inconnue');
            return $this->redirectToRoute('app_catalogue_pieces_detachees');
        }

        $yearInDescription = $boite->getYear();
        if($yearInDescription == 0){
            $yearInDescription = 'inconnue';
        }
        $metas['description'] = 'Les pièces détachées du jeu: '.ucfirst(strtolower($boite->getName())).' - '.ucfirst(strtolower($boite->getEditor()->getName())).' - Année '.$yearInDescription;
        $metas['index'] = 'index, follow';
        
        $affichages = [];
        $items = $boite->getItemsOrigine();
        $totalItems = 0;
        $nbrItems = 0;
        foreach($items as $item){
            $totalItems += $item->getStockForSale();
            if($item->getStockForSale() > 0){
                $nbrItems++;
            }
        }

        $affichages['totalItems'] = $nbrItems;

        if($totalItems == 0){
            $this->addFlash('warning', 'Plus d\'articles en vente');
            return $this->redirectToRoute('app_catalogue_pieces_detachees');
        }

        $groups = [];
        foreach($items as $item){
            if(!array_key_exists($item->getItemGroup()->getId(),$groups)){
                if($item->getStockForSale() > 0){
                    $count = 1;
                }else{
                    $count = 0;
                }
                $groups[$item->getItemGroup()->getId()] = [
                    'group' => $item->getItemGroup(),
                    'items' => [$item],
                    'count' => $count,
                ];
            } else {
                $groups[$item->getItemGroup()->getId()]['items'][] = $item;
                $groups[$item->getItemGroup()->getId()]['count'] = $groups[$item->getItemGroup()->getId()]['count'] + 1;
            }
        }

        //?grilles tarifaires d'expedition (par pays dessservi) utilisees pour les donnees structurees schema.org (shippingDetails)
        $shippingMethod = $this->shippingMethodRepository->findOneBy(['isActivedInCart' => true, 'forOccasionOnly' => false]);
        $deliveryTiersByCountry = [];
        if ($shippingMethod) {
            foreach ($this->countryRepository->findBy(['name' => ['FRANCE', 'BELGIQUE']]) as $country) {
                $deliveryTiersByCountry[] = [
                    'isocode' => $country->getIsocode(),
                    'tiers' => $this->deliveryRepository->findBy(['shippingMethod' => $shippingMethod, 'country' => $country], ['start' => 'ASC']),
                ];
            }
        }

        return $this->render('site/pages/catalog/pieces_detachees/articles_d_une_boite.html.twig', [
            'boite' => $boite,
            'metas' => $metas,
            'groups' => $groups,
            'affichages' => $affichages,
            'search' => $search ?? null,
            'tax' => $this->taxRepository->findOneBy([]),
            'deliveryTiersByCountry' => $deliveryTiersByCountry,
        ]);
    }

}
