<?php

namespace App\Controller\Admin;

use App\Repository\ItemRepository;
use App\Repository\BoiteRepository;
use App\Repository\UserRepository;
use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;

class StatsController extends AbstractController
{
    public function __construct(
        private ItemRepository $itemRepository,
        private BoiteRepository $boiteRepository,
        private UserRepository $userRepository,
        private DocumentRepository $documentRepository,
    ) {
    }

    #[AdminRoute('/stats', name: 'stats')]
    public function index(): Response
    {
        return $this->render('admin/stats/index.html.twig', [
            'bestSellingItems' => $this->itemRepository->findBestSellingItems(10),
            'averages' => $this->boiteRepository->findAverageWeightAndPrice(),
            'bestSellingGameNames' => $this->boiteRepository->findGameNamesWithMostArticlesSold(10),
            'topClients' => $this->userRepository->findTopClientsByPaidOrders(15),
            'clientDePassage' => $this->userRepository->findClientDePassageOrderCount(),
            'deliveriesMap' => $this->buildDeliveriesMap(),
        ]);
    }

    //?Carte des livraisons regroupees par ville (France/Belgique) : un marqueur par ville,
    //?avec le nombre de commandes livrees la-bas.
    private function buildDeliveriesMap(): Map
    {
        $rows = $this->documentRepository->countDocumentsGroupedByDeliveryCity();

        $map = (new Map())
            ->center(new Point(47.0, 2.5))
            ->zoom(5.5)
            //?Sans options explicites, la carte part sans fond de carte (aucune tuile) : le
            //?JS applique alors des defauts a false (tileLayer/zoomControl/attribution).
            ->options(new LeafletOptions());

        foreach ($rows as $row) {
            $map->addMarker(new Marker(
                position: new Point((float) $row['latitude'], (float) $row['longitude']),
                title: $row['name'] . ' (' . $row['total'] . ')',
                infoWindow: new InfoWindow(
                    headerContent: $row['name'],
                    content: $row['total'] . ' livraison' . ($row['total'] > 1 ? 's' : ''),
                ),
            ));
        }

        return $map;
    }
}
