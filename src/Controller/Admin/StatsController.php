<?php

namespace App\Controller\Admin;

use App\Repository\ItemRepository;
use App\Repository\BoiteRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

class StatsController extends AbstractController
{
    public function __construct(
        private ItemRepository $itemRepository,
        private BoiteRepository $boiteRepository,
        private UserRepository $userRepository,
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
        ]);
    }
}
