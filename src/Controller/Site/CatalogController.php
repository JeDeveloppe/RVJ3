<?php

namespace App\Controller\Site;

use App\Entity\ItemGroup;
use App\Form\RequestForBoxType;
use App\Service\PanierService;
use App\Service\AdresseService;
use App\Service\OccasionService;
use App\Repository\TaxRepository;
use App\Service\UtilitiesService;
use App\Repository\BoiteRepository;
use App\Repository\EditorRepository;
use App\Repository\AddressRepository;
use App\Repository\PartnerRepository;
use App\Repository\OccasionRepository;
use App\Form\SearchBoiteInCatalogueType;
use App\Form\SearchOccasionNameOrEditorInCatalogueType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SiteSettingRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Form\SearchOccasionsInCatalogueType;
use App\Repository\CollectionPointRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CatalogOccasionSearchRepository;
use App\Repository\DurationOfGameRepository;
use App\Repository\ItemGroupRepository;
use App\Repository\ItemRepository;
use App\Service\CatalogControllerService;
use App\Service\CatalogueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class CatalogController extends AbstractController
{
    public function __construct(
        private BoiteRepository $boiteRepository,
        private OccasionRepository $occasionRepository,
        private PaginatorInterface $paginator,
        private EditorRepository $editorRepository,
        private PartnerRepository $partnerRepository,
        private TaxRepository $taxRepository,
        private PanierService $panierService,
        private OccasionService $occasionService,
        private AddressRepository $addressRepository,
        private CollectionPointRepository $collectionPointRepository,
        private AdresseService $adresseService,
        private SiteSettingRepository $siteSettingRepository,
        private CatalogOccasionSearchRepository $catalogOccasionSearchRepository,
        private UtilitiesService $utilitiesService,
        private EntityManagerInterface $em,
        private Security $security,
        private CatalogueService $catalogueService,
        private DurationOfGameRepository $durationOfGameRepository,
        private RequestStack $requestStack,
        private CatalogControllerService $catalogControllerService,
        private ItemRepository $itemRepository,
        private ItemGroupRepository $itemGroupRepository,
    )
    {
    }

    #[Route('/catalogue-pieces-detachees', name: 'app_catalogue_pieces_detachees')]
    public function cataloguePiecesDetachees(Request $request): Response
    {

        //?on supprimer les paniers de plus de x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();
        $siteSetting = $this->siteSettingRepository->findOneBy([]);
        $orderColumn = $request->query->get('orderColumn') ?? NULL;
        $activeTriWhereThereIsNoSearch = true;

        $form = $this->createForm(SearchBoiteInCatalogueType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $activeTriWhereThereIsNoSearch = false;
            $search = $form->get('search')->getData();
            $donneesFromDatabases = $this->boiteRepository->findBoitesWhereThereIsItems($search);


        }else{

            //si on cherche par ordre des noms de boite
            if($orderColumn == 'name'){

                $items = $this->itemRepository->findAllItemsWithStockForSaleNotNull();

            }else{
                
                //si on cherche par ordre des derniers articles ajoutés
                $items = $this->itemRepository->findAllItemsWithStockForSaleNotNullOrderByUpdatedAtDesc();
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

        return $this->render('site/pages/catalog/pieces_detachees/articles_d_une_boite.html.twig', [
            'boite' => $boite,
            'metas' => $metas,
            'groups' => $groups,
            'affichages' => $affichages,
            'search' => $search ?? null,
            'tax' => $this->taxRepository->findOneBy([]),
        ]);
    }

    // #[Route('/catalogue-jeux-occasion/{category}', name: 'app_catalogue_occasions')]
    // public function catalogueOccasions(Request $request, $category = NULL): Response
    // {

    //     //?on supprimer les paniers de plus de x heures
    //     $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

    //     $metas['description'] = "Plein de jeux de société d'occasion à petit prix ! Idéal pour les soirées entre amis ou en famille. Visitez nos catalogues et trouvez votre bonheur !";

    //     //values for form / query in repository etc...
    //     $choices = $this->occasionService->returnOptionsForFormAndTitleForOccasionCatalogByCategory($category);

    //     //génération du form avec les options
    //     $form = $this->createForm(SearchOccasionsInCatalogueType::class, null,
    //         [
    //             'method' => 'GET',
    //             'agesOptions' => $choices['ages_options_for_form'],
    //             'playersOptions' => $choices['players_options_for_form'],
    //             'durationsOptions' => $choices['durations_options_for_form'],
    //         ]);
    //     $form->handleRequest($request);

    //     //génération du form recherche name or editor
    //     $formNameOrEditor = $this->createForm(SearchOccasionNameOrEditorInCatalogueType::class, null, ['method' => 'GET',]);
    //     $formNameOrEditor->handleRequest($request);

    //     // if($form->isSubmitted() && $form->isValid()) {
    //     if($form->isSubmitted() OR $formNameOrEditor->isSubmitted() && $formNameOrEditor->isValid()) {

    //         $search = str_replace(" ","%", $formNameOrEditor->get('search')->getData()) ?? '';
    //         $ages = $form->get('ages')->getData() ?? [];
    //         $players = $form->get('players')->getData() ?? [];
    //         $durations = $form->get('durations')->getData() ?? [];

    //         //? on sauvegarde les 100 dernières recherche des utilisateurs
    //         // $this->catalogueService->saveSearchOccasionInDataBase($request, $phrase, $age_start, $players, $durations, 100);

    //         //?si on recherche un nom
    //         if($search != ''){

    //             //?si on cherche des jeux par nom ou par editeur on force retur au catalogue de tous les jeux par default
    //             $choices = $this->occasionService->returnOptionsForFormAndTitleForOccasionCatalogByCategory('tous-les-jeux');
    //             $donneesFromDatabases = $this->occasionRepository->findOccasionsInCatalogueByNameOrEditor($search, $choices);

    //         }else{

    //             //?si on cherche un seul nombre de joueurs
    //             if(count($players) == 1){

    //                 $donneesFromDatabasesPlayerMinAndPlayerMaxAsSame = $this->occasionRepository->findOccasionsInCatalogueWherePlayerMinAndPlayerMaxAreSame($players, $durations, $ages, $choices);
    //                 $donneesFromDatabasePlayersBetweenMinAndMax = $this->occasionRepository->findOccasionsInCatalogueWherePlayersBetweenMinAndMax($players, $durations, $ages, $choices);
    //                 //?on retire les doublons
    //                 $donneesFromDatabases = array_unique(array_merge($donneesFromDatabasesPlayerMinAndPlayerMaxAsSame,$donneesFromDatabasePlayersBetweenMinAndMax), SORT_REGULAR); //?on retire les doublons
                
    //             }else{

    //                 $donneesFromDatabasesAll = $this->occasionRepository->searchOccasionsInCatalogue($ages, $players, $durations, $choices);
    //                 $donneesFromDatabasesPlayerMinAndPlayerMaxAsSame = $this->occasionRepository->findOccasionsInCatalogueWherePlayerMinAndPlayerMaxAreSame($players, $durations, $ages, $choices);

    //                 $donneesFromDatabases = array_unique(array_merge($donneesFromDatabasesAll,$donneesFromDatabasesPlayerMinAndPlayerMaxAsSame), SORT_REGULAR); //?on retire les doublons

    //             }

    //         }


    //     }else{

    //         //on cherche l'ensemble du catalogue
    //         $donneesFromDatabases = $this->occasionRepository->searchOccasionsInCatalogue([], [], [], $choices);

    //     }
    //     // $diff = $this->catalogueService->returnOccasionsWithoutOccasionsInCart($donneesFromDatabases);


    //     $occasions = $this->paginator->paginate(
    //         $donneesFromDatabases, /* query NOT result */
    //         $request->query->getInt('page', 1), /*page number*/
    //         24, /*limit per page*/
    //     );

 
    //     //si url contient ajax et surtout ne contient pas page
    //     if($request->get('ajax') && !$request->get('page') && $search == '') {

    //         return new JsonResponse([
    //             'content' => $this->renderView('site/pages/catalog/components/_display_occasions_results.html.twig', [
    //                 'occasions' => $occasions,
    //                 'occasions_totales' => $donneesFromDatabases,
    //                 'metas' => $metas,
    //                 'titreDeLaPage' => $choices['twig']['titleH1'],
    //                 'title' => $choices['twig']['title'],
    //                 'breadcrumb' => $choices['twig']['breadcrumb'],
    //                 'form' => $form,
    //                 'formNameOrEditor' => $formNameOrEditor,
    //                 'tax' => $this->taxRepository->findOneBy([]),
    //                 'partners' => $this->partnerRepository->findBy(['isOnline' => true, 'isDisplayOnCatalogueWhenSearchIsNull' => true]),
    //             ])
    //         ]);

    //     }else{

    //         return $this->render('site/pages/catalog/occasions/les_occasions.html.twig', [
    //             'occasions' => $occasions,
    //             'occasions_totales' => $donneesFromDatabases,
    //             'metas' => $metas,
    //             'titreDeLaPage' => $choices['twig']['titleH1'],
    //             'title' => $choices['twig']['title'],
    //             'breadcrumb' => $choices['twig']['breadcrumb'],
    //             'form' => $form,
    //             'formNameOrEditor' => $formNameOrEditor,
    //             'tax' => $this->taxRepository->findOneBy([]),
    //             'partners' => $this->partnerRepository->findBy(['isOnline' => true, 'isDisplayOnCatalogueWhenSearchIsNull' => true]),
    //         ]);

    //     }
    // }

    // #[Route('/jeu-occasion/{reference_occasion}/{editor_slug}/{boite_slug}', name: 'occasion')]
    // public function occasion($reference_occasion, Security $security, $editor_slug, $boite_slug): Response
    // {

    //     //?on supprimer les paniers de plus de x heures
    //     $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

    //     $delivery = null;
    //     $http_error_code = 200;

    //     $occasions = $this->occasionRepository->findUniqueOccasionWhenReferenceAndSlugAreOk($reference_occasion, $editor_slug, $boite_slug);

    //     //si on ne trouve pas d'occasion on regarde avec inversion dans la reference v3 et v2
    //     if(!$occasions){

    //         $occasions = $this->occasionRepository->findUniqueOccasionByRefrenceV2AndSlugsAreOk($reference_occasion, $editor_slug, $boite_slug);
    //         $occasion = $occasions[0];
    //         //se sera une redirection permanente
    //         $http_error_code = 301;

    //         if(!$occasion){
    //             //si on a pas trouver en inversant la referance
    //             $http_error_code = 404;
    //             throw $this->createNotFoundException('Occasion non trouvée');

    //         }else{

    //             //on inverse les references - v2 et v3
    //             $references = explode('-', $reference_occasion);
    //             //et on redirige vers la meme page
    //             return $this->redirectToRoute('occasion', [
    //                 'reference_occasion' => $references[1].'-'.$references[0],
    //                 'editor_slug' => $occasion->getBoite()->getEditor()->getSlug(),
    //                 'boite_slug' => $occasion->getBoite()->getSlug(),
    //             ], $http_error_code);
    //         }
    //     }

    //     $user = $security->getUser();

    //     if($user){

    //         $deliveryAdresse = $this->addressRepository->findOneBy(['user' => $user, 'isFacturation' => false]);

    //         if(is_null($deliveryAdresse)){

    //             $delivery = null;

    //         }else{

    //             $collectionPoint = $this->collectionPointRepository->findOneCollectionPointForOccasionBuy();

    //             $kmsBetweenCollectionPointAndDeliveryAdress = $this->adresseService->get_distance_from_collectePoint($collectionPoint, $deliveryAdresse);
                
    //             $setting = $this->siteSettingRepository->findOneBy([]);

    //             if($kmsBetweenCollectionPointAndDeliveryAdress < $setting->getDistanceMaxForOccasionBuy()){

    //                 $delivery = true;

    //             }else{

    //                 $delivery = false;

    //             }
    //         }

    //     }


    //     $query = $this->occasionRepository->findAleatoireOccasionsByAgeWhitoutThisOccasion($occasions[0]->getBoite()->getAge(), $occasions[0]);
    //     shuffle($query); // on mélange
    //     $firstElements = array_slice($query, 0, 4); //on prend les 6 premiers apres avoir mélanger
    //     $metas['description'] = 'Jeu d\'occasion vérifié, remis en état, et disponible à petit prix: '.ucfirst(strtolower($occasions[0]->getBoite()->getName())).' - '.ucfirst(strtolower($occasions[0]->getBoite()->getEditor()->getName()).' - Notre référence: '.$occasions[0]->getReference());

    //     return $this->render('site/pages/catalog/occasions/occasion.html.twig', [
    //         'occasion' => $occasions[0],
    //         'tax' => $this->taxRepository->findOneBy([]),
    //         'metas' => $metas,
    //         'delivery' => $delivery,
    //         'firstElements' => $firstElements
    //     ], new Response(null, $http_error_code));
    // }
}
