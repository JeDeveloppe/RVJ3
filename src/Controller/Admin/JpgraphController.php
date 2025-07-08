<?php

namespace App\Controller\Admin;

use App\Service\JpgraphService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class JpgraphController extends AbstractController
{
    public function __construct(
        private JpgraphService $jpgraphService
        ){
    }

    #[Route('/admin/jpgraph', name: 'jpgraph')]
    public function index(): Response
    {
        return $this->render('admin/jpgraph/index.html.twig', [
            'controller_name' => 'JpgraphController',
        ]);
    }

    #[Route('/admin/jpgraph/ca-by-year/', name: 'jpgraph_ca_by_year')]
    public function jpgraphByYear(Request $request): Response
    {
        $this->jpgraphService->graphCA_Annuel($request->query->get('year'));

        return new Response();
    }

    #[Route('/admin/jpgraph/ca-between-two-years/', name: 'jpgraph_ca_between_two_years')]
    public function jpgraphBetweenTwoYears(Request $request): Response
    {
        $this->jpgraphService->graphCA_Between_2_years($request->query->get('year'));

        return new Response();
    }

    #[Route('/admin/jpgraph/transactions-by-year/', name: 'jpgraph_transactions_by_year')]
    public function jpgraphTransactionsByYear(Request $request): Response
    {
        $this->jpgraphService->graphTransactionsByYear($request->query->get('year'));

        return new Response();
    }

    #[Route('/admin/jpgraph/repartition-transactions-by-year/', name: 'jpgraph_repartition_transactions_by_year')]
    public function jpgraphRepartitionsTransactionsByYear(Request $request): Response
    {
        $this->jpgraphService->graphRepartitionTransactionByYear($request->query->get('year'));

        return new Response();
    }

    #[Route('/admin/jpgraph/inscriptions-by-year/', name: 'jpgraph_inscriptions_by_year')]
    public function jpgraphInscriptionsByYear(Request $request): Response
    {
        $this->jpgraphService->graphInscriptionsByYear($request->query->get('year'));

        return new Response();
    }

    #[Route('/admin/jpgraph/repartition-boites-completed-by-year/', name: 'jpgraph_repartition_boites_completed_by_year')]
    public function jpgraphRepartitionBoitesCompletedsByYear(Request $request): Response
    {
        $this->jpgraphService->graphNumberOfBoitesCompletedByYear($request->query->get('year'));

        return new Response();
    }
    
}

