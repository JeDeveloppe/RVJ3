<?php

namespace App\Service;

use DateTime;
use Amenadiel\JpGraph\Graph\Graph;
use App\Repository\UserRepository;
use Amenadiel\JpGraph\Plot\BarPlot;
use Amenadiel\JpGraph\Graph\PieGraph;
use Amenadiel\JpGraph\Plot\PiePlot3D;
use App\Repository\PaymentRepository;
use Amenadiel\JpGraph\Plot\AccBarPlot;
use Amenadiel\JpGraph\Themes\AquaTheme;
use Amenadiel\JpGraph\Plot\GroupBarPlot;
use Amenadiel\JpGraph\Themes\VividTheme;
use App\Repository\DocumentLineRepository;
use Amenadiel\JpGraph\Graph\Graph as GraphGraph;

class JpgraphService
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private DocumentLineRepository $documentLineRepository,
        private UserRepository $userRepository
        ){
    }

    public function graphCA_Annuel($annee)
    {
        $totaux = [];
            for($m=1;$m<=12;$m++){
                $result = $this->paymentRepository->findPaiementsAndReturnCA($m,$annee);
                $result_100 = intval(number_format($result / 100,2));
                array_push($totaux,$result_100);
            }

        $totalAnnuel = array_sum($totaux);

        $data1y=$totaux;
        
        // Create the graph. These two calls are always required
        $graph = new GraphGraph(1050,600,'auto');
        $graph->SetScale("textlin");
        
        $theme_class = new VividTheme;
        $graph->SetTheme($theme_class);
        
        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);
        
        $graph->ygrid->SetFill(false);
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);
        
        // Create the bar plots
        $b1plot = new BarPlot($data1y);
        $b1plot->SetLegend($annee);
        
        
        // Create the grouped bar plot
        $gbplot = new GroupBarPlot(array($b1plot));
        // ...and add it to the graPH
        $graph->Add($gbplot);
        $graph->legend->SetPos(0.5,0.92,'center','bottom');
        
        
        $b1plot->SetColor("white");
        $b1plot->SetFillColor("#cc1111");
        $b1plot->value->Show();
        
        $graph->title->Set("CA des ventes par mois en ".$annee." \n Total HT: ".$totalAnnuel);
        // Display the graph
        $graph->Stroke();
    }

    public function graphCA_Between_2_years($anneeN)
    {
        $totaux1y = [];
        for($m=1;$m<=12;$m++){
            $result = $this->paymentRepository->findPaiementsAndReturnCA($m,$anneeN);
            $result_100 = intval(number_format($result / 100,2));
            array_push($totaux1y,$result_100);
        }
        $data1y=$totaux1y;

        $anneeN_1 = $anneeN-1;
        $totaux2y = [];
        for($m=1;$m<=12;$m++){
            $result = $this->paymentRepository->findPaiementsAndReturnCA($m,$anneeN_1);
            $result_100 = intval(number_format($result / 100,2));
            array_push($totaux2y,$result_100);
        }
        $data2y=$totaux2y;

        // Create the graph. These two calls are always required
        $graph = new Graph(1050,600,'auto');
        $graph->SetScale("textlin");

        //choix du theme
        $theme_class = new AquaTheme;
        $graph->SetTheme($theme_class);

        //axe des Y
        //$graph->yaxis->SetTickPositions(array(0,30,60,90,120,150,180,210,240,270,300), array(15,45,75,105,135,165,195,225));
        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

        // Create the bar plots
        $b1plot = new BarPlot($data1y);
        $b1plot->SetLegend($anneeN);
        $b2plot = new BarPlot($data2y);
        $b2plot->SetLegend($anneeN_1);

        // Create the grouped bar plot
        $gbplot = new GroupBarPlot(array($b2plot,$b1plot));
        // ...and add it to the graPH
        $graph->Add($gbplot);
        $graph->legend->SetPos(0.5,0.92,'center','bottom');


        $b1plot->SetColor("white");
        $b1plot->SetFillColor("#cc1111");
        $b1plot->value->Show();

        $b2plot->SetColor("white");
        $b2plot->SetFillColor("#11cccc");
        $b2plot->value->Show();

        $graph->title->Set("Ventes par mois (HT) ".$anneeN_1." / ".$anneeN);


        // Display the graph
        $graph->Stroke();
    }

    public function graphTransactionsByYear($anneeN)
    {
        $totalVentes = [];

        for($m=1;$m<=12;$m++){
            // $sqlVentes = $bdd->prepare("SELECT SUM(qte) as totalQte FROM documents_lignes_achats dl LEFT JOIN documents d ON dl.idDocument = d.idDocument WHERE MONTH(FROM_UNIXTIME(d.time_transaction)) = ? AND YEAR(FROM_UNIXTIME(d.time_transaction)) = ? AND etat = 2 ");
            // $result = $this->documentLignesRepository->findBoitesVendues($m,$anneeN);
            $paiementsNumber = $this->paymentRepository->findNumberOfPaiements($m,$anneeN);

            array_push($totalVentes,$paiementsNumber);
            
        }

        $data1y = $totalVentes;
        
        // Create the graph. These two calls are always required
        $graph = new GraphGraph(1050,600,'auto');
        $graph->SetScale("textlin");
        
        $theme_class = new VividTheme;
        $graph->SetTheme($theme_class);
        
        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);
        
        $graph->ygrid->SetFill(false);
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);
        
        // Create the bar plots
        $b1plot = new BarPlot($data1y);
        $b1plot->SetLegend($anneeN);
        
        
        // Create the grouped bar plot
        $gbplot = new GroupBarPlot(array($b1plot));
        // ...and add it to the graPH
        $graph->Add($gbplot);
        $graph->legend->SetPos(0.5,0.92,'center','bottom');
        
        
        $b1plot->SetColor("white");
        $b1plot->SetFillColor("#cc1111");
        $b1plot->value->Show();
        
        $graph->title->Set("Quantité de ventes par mois en ".$anneeN." \n Total des ventes: ".array_sum($totalVentes));
        // Display the graph
        $graph->Stroke();
    }

    public function graphRepartitionTransactionByYear($annee)
    {

        $totalPaiementsInYear = [];

        for($m=1;$m<=12;$m++){
            // $sqlVentes = $bdd->prepare("SELECT SUM(qte) as totalQte FROM documents_lignes_achats dl LEFT JOIN documents d ON dl.idDocument = d.idDocument WHERE MONTH(FROM_UNIXTIME(d.time_transaction)) = ? AND YEAR(FROM_UNIXTIME(d.time_transaction)) = ? AND etat = 2 ");
            // $result = $this->documentLignesRepository->findBoitesVendues($m,$anneeN);
            $paiementsInMonthByYear = $this->paymentRepository->findPaiements($m,$annee);

            $totalPaiementsInYear[DateTime::createFromFormat('!m', $m)->format('F')] = $paiementsInMonthByYear;
        }
        $totalTransactionByMonthByColumn = [];

        $ventes = [];

        foreach($totalPaiementsInYear as $totalPaiementsByMonth){
            $items = 0;
            $boites = 0;
            $occasions = 0;
            $totalArticles = 0;
            $ventes = ['articles' => $items, 'detachees' => $boites, 'occasions' => $occasions, 'totalItems' => $totalArticles];

            foreach($totalPaiementsByMonth as $paiement){
                
                $docLines = $paiement->getDocument()->getDocumentLines();
                foreach($docLines as $docLine){
    
                    if($docLine->getItem() != NULL){
                        $items += 1;
                        $totalArticles += $docLine->getQuantity();
                    }
                    if($docLine->getBoite() != NULL){
                        $boites += 1;
                    }
                    if($docLine->getOccasion() != NULL){
                        $occasions += 1;
                    }
                }
                $ventes = ['articles' => $items, 'detachees' => $boites, 'occasions' => $occasions, 'totalItems' => $totalArticles];

            }
            array_push($totalTransactionByMonthByColumn,$ventes);
        }

        $bp_items = [];
        $bp_boites = [];
        $bp_occasions = [];
        foreach($totalTransactionByMonthByColumn as $months){
            $bp_items[] = $months['articles'];
            $bp_boites[] = $months['detachees'];
            $bp_occasions[] = $months['occasions'];
            $bp_totalItems[] = $months['totalItems'];
        }
        
        // Create the graph. These two calls are always required
        $graph = new GraphGraph(1050,600,'auto');
        $graph->SetScale("textlin");

        $theme_class = new VividTheme;
        $graph->SetTheme($theme_class);

        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

        // Create the bar plots
        $b1plot = new BarPlot($bp_boites);
        $b1plot->SetFillColor('blue');
        $b1plot->value->SetFont(FF_ARIAL,FS_BOLD);
        $b1plot->value->Show();
        $b1plot->SetLegend('Pièces détachées');

        $b2plot = new BarPlot($bp_occasions);
        $b2plot->SetFillColor('orange');
        $b2plot->value->Show();
        $b2plot->SetLegend('Occasions');

        $b3plot = new BarPlot($bp_items);
        $b3plot->SetFillColor('green');
        $b3plot->value->Show();
        $b3plot->SetLegend('Articles');

        // Create the grouped bar plot
        $gbplot1 = new AccBarPlot(array($b1plot,$b2plot,$b3plot));

        // Create the stacked bar plot
        $gbplot2 = new BarPlot($bp_totalItems);
        $gbplot2->SetFillColor('red');
        $gbplot2->value->Show();
        $gbplot2->SetLegend('Nbr total articles vendus');

        // Add the plots to the grouped bar plot
        $gbplot = new GroupBarPlot(array($gbplot1,$gbplot2));

        // ...and add it to the graPH
        $graph->Add($gbplot);
        $graph->legend->SetPos(0.5,0.92,'center','bottom');



        $graph->title->Set("Nombre de demandes / groupe sur ".$annee);

        // Display the graph
        $graph->Stroke();
    }

    public function graphInscriptionsByYear($annee)
    {
        $total = [];

            for($m=1;$m<=12;$m++){
                // $sqlVentes = $bdd->prepare("SELECT SUM(qte) as totalQte FROM documents_lignes_achats dl LEFT JOIN documents d ON dl.idDocument = d.idDocument WHERE MONTH(FROM_UNIXTIME(d.time_transaction)) = ? AND YEAR(FROM_UNIXTIME(d.time_transaction)) = ? AND etat = 2 ");
                // $result = $this->documentLignesRepository->findBoitesVendues($m,$anneeN);
                $inscriptions = $this->userRepository->findInscriptions($m,$annee);

                if(count($inscriptions) < 1){
                    array_push($total,0);
                }else{
                    array_push($total,count($inscriptions));
                }
            }

        $data1y = $total;
        
        // Create the graph. These two calls are always required
        $graph = new GraphGraph(1050,600,'auto');
        $graph->SetScale("textlin");
        
        $theme_class = new VividTheme;
        $graph->SetTheme($theme_class);
        
        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);
        
        $graph->ygrid->SetFill(false);
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);
        
        // Create the bar plots
        $b1plot = new BarPlot($data1y);
        $b1plot->SetLegend($annee);
        
        
        // Create the grouped bar plot
        $gbplot = new GroupBarPlot(array($b1plot));
        // ...and add it to the graPH
        $graph->Add($gbplot);
        $graph->legend->SetPos(0.5,0.92,'center','bottom');
        
        
        $b1plot->SetColor("white");
        $b1plot->SetFillColor("#cc1111");
        $b1plot->value->Show();
        
        $graph->title->Set("Inscriptions par mois en ".$annee." \n Total des inscrits: ".array_sum($total));
        
        // Display the graph
        $graph->Stroke();
    }

    public function graphNumberOfBoitesCompletedByYear($annee)
    {

        $totalPaiementsInYear = [];

        for($m=1;$m<=12;$m++){
            // $sqlVentes = $bdd->prepare("SELECT SUM(qte) as totalQte FROM documents_lignes_achats dl LEFT JOIN documents d ON dl.idDocument = d.idDocument WHERE MONTH(FROM_UNIXTIME(d.time_transaction)) = ? AND YEAR(FROM_UNIXTIME(d.time_transaction)) = ? AND etat = 2 ");
            // $result = $this->documentLignesRepository->findBoitesVendues($m,$anneeN);
            $paiementsInMonthByYear = $this->paymentRepository->findPaiements($m,$annee);

            $totalPaiementsInYear[DateTime::createFromFormat('!m', $m)->format('F')] = $paiementsInMonthByYear;
        }

        $totalTransactionByMonthByColumn = [];
        $ventes = [];
        $totalAnnuel = 0;

        foreach($totalPaiementsInYear as $totalPaiementsByMonth){
            $items = 0;
            $boites = 0;
            $ventes = 0;

            foreach($totalPaiementsByMonth as $paiement){
                
                $docLines = $paiement->getDocument()->getDocumentLines();
                $boitesCompletedWithItems = [];
                $boitesCompletesWithBoites = [];
                foreach($docLines as $docLine){

                    if($docLine->getItem() != NULL){
                        if(!in_array($docLine->getItem()->getBoiteOrigine(), $boitesCompletedWithItems)){
                            $boitesCompletedWithItems[] = $docLine->getItem()->getBoiteOrigine();
                            $items += 1;
                        }
                    }
                    if($docLine->getBoite() != NULL){
                        if(!in_array($docLine->getBoite(), $boitesCompletesWithBoites)){
                            $boitesCompletesWithBoites[] = $docLine->getBoite();
                            $boites += 1;
                        }
                    }
                }
                $ventes = $items + $boites;

            }
            array_push($totalTransactionByMonthByColumn,$ventes);
            $totalAnnuel += $ventes;
        }

        // Create the graph. These two calls are always required
        $graph = new GraphGraph(1050,600,'auto');
        $graph->SetScale("textlin");

        $theme_class = new VividTheme;
        $graph->SetTheme($theme_class);

        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

        // Create the bar plots
        $b1plot = new BarPlot($totalTransactionByMonthByColumn);
        $b1plot->SetFillColor('blue');
        $b1plot->value->SetFont(FF_ARIAL,FS_BOLD);
        $b1plot->value->Show();
        $b1plot->SetLegend('Jeux complétés');

        // ...and add it to the graPH
        $graph->Add($b1plot);
        $graph->legend->SetPos(0.5,0.92,'center','bottom');

        $graph->title->Set("Nombre de jeux complétés sur ".$annee." \n Total des jeux complétés: ".$totalAnnuel);

        // Display the graph
        $graph->Stroke();
    }
}