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

class StructureController extends AbstractController
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
    

    #[Route('structure-adherentes/catalogue-pieces-detachees', name: 'structure_catalogue_pieces_detachees')]
    public function cataloguePiecesDetacheesForStructure(Request $request): Response
    {

        //?on supprimer les paniers de plus de x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();
        $siteSetting = $this->siteSettingRepository->findOneBy([]);
        $activeTriWhereThereIsNoSearch = true;

        $form = $this->createForm(SearchBoiteInCatalogueType::class, null, ['method' => 'GET']);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $activeTriWhereThereIsNoSearch = false;
            $search = $form->get('search')->getData();
            $donnees = $this->boiteRepository->findBoitesForMemberStructure($search);

        }else{

            $donnees = $this->boiteRepository->findBy([], ['id' => 'DESC']);

        }

        $boitesBox = $this->paginator->paginate(
            $donnees, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            12 /*limit per page*/
        );


        $metas['description'] = 'Catalogue complet de toutes les boites dont le service dispose de pièces détachées.';

        return $this->render('site/pages/structures/pieces_detachees_structures.html.twig', [
            'boitesBox' => $boitesBox,
            'allBoites' => count($donnees),
            'form' => $form,
            'search' => $search ?? null,
            'activeTriWhereThereIsNoSearch' => $activeTriWhereThereIsNoSearch,
            'metas' => $metas,
            'forStructure' => true,
            'tax' => $this->taxRepository->findOneBy([]),
            'siteSetting' => $siteSetting
        ]);
    }

    #[Route('structure-adherentes//catalogue-pieces-detachees/demande/{id}/{editorSlug}/{boiteSlug}/', name: 'structure_catalogue_pieces_detachees_demande', requirements: ['boiteSlug' => '[a-z0-9\-]+'] )]
    public function cataloguePiecesDetacheesForStructureDemande(Request $request, $id, $editorSlug, $boiteSlug, $year = NULL, $search = NULL): Response
    {
        $form = $this->createForm(RequestForBoxType::class);
        $form->handleRequest($request);
        //?on supprimer les paniers de plus de x heures
        $this->panierService->deletePanierFromDataBaseAndPuttingItemsBoiteOccasionBackInStock();

        $boite = $this->boiteRepository->findOneBy(['id' => $id, 'slug' => $boiteSlug, 'editor' => $this->editorRepository->findOneBy(['slug' => $editorSlug])]);

        if(!$boite){
            $this->addFlash('warning', 'Boite inconnue');
            return $this->redirectToRoute('app_catalogue_pieces_detachees');
        }

        $yearInDescription = $boite->getYear();
        if($yearInDescription == 0){
            $yearInDescription = 'inconnue';
        }
        $metas['description'] = 'Boite de jeu: '.ucfirst(strtolower($boite->getName())).' - '.ucfirst(strtolower($boite->getEditor()->getName())).' - Année '.$yearInDescription;

        if($form->isSubmitted() && $form->isValid()){
            $this->panierService->addBoiteRequestToCart($request, $boite);
            $this->addFlash('success', 'Demande dans le panier !');
            
            return $this->redirectToRoute('structure_catalogue_pieces_detachees');
        }
  


        return $this->render('site/pages/structures/pieces_detachees_demande.html.twig', [
            'boite' => $boite,
            'metas' => $metas,
            'form' => $form->createView(),
            'search' => $search ?? null,
            'tax' => $this->taxRepository->findOneBy([]),
        ]);
    }
}
