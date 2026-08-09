<?php

namespace App\Service;

use DateTime;
use Amenadiel\JpGraph\Graph\Graph;
use App\Repository\UserRepository;
use Amenadiel\JpGraph\Plot\BarPlot;
use Amenadiel\JpGraph\Plot\LinePlot;
use Amenadiel\JpGraph\Graph\PieGraph;
use Amenadiel\JpGraph\Plot\PiePlot3D;
use App\Repository\PaymentRepository;
use Amenadiel\JpGraph\Plot\AccBarPlot;
use Amenadiel\JpGraph\Plot\GroupBarPlot;
use Amenadiel\JpGraph\Themes\UniversalTheme;
use App\Repository\DocumentLineRepository;
use Amenadiel\JpGraph\Graph\Graph as GraphGraph;

// Vocabulaire utilise dans ce fichier - trois unites bien distinctes, a ne pas confondre :
// - "commande" / "paiement confirme" : une ligne de la table payment avec timeOfTransaction
//   renseigne (cf. PaymentRepository::findPaiements/findNumberOfPaiements). Une personne qui
//   achete 3 pieces differentes en une fois = 1 commande.
// - "ligne(s)" : une ligne de document_line (item_id/boite_id/occasion_id). Une commande peut
//   contenir plusieurs lignes (un article distinct = une ligne).
// - "quantite" / "unites vendues" : SUM(document_line.quantity). Une ligne peut a elle seule
//   valoir plusieurs unites (ex: quantite = 16 sur une seule ligne).
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

        $theme_class = new UniversalTheme;
        $graph->SetTheme($theme_class);

        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->ygrid->SetColor('#e8e8e8');
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->xaxis->SetColor('#888888', '#444444');
        $graph->yaxis->SetColor('#888888', '#444444');
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

        // Create the bar plots
        $b1plot = new BarPlot($data1y);

        // Create the grouped bar plot
        $gbplot = new GroupBarPlot(array($b1plot));
        // ...and add it to the graPH
        $graph->Add($gbplot);
        // une seule serie : le titre porte deja l'annee, pas besoin de legende
        $graph->legend->Hide();

        $b1plot->SetColor("#2a78d6");
        $b1plot->SetFillColor("#2a78d6");
        $b1plot->value->Show();
        $b1plot->value->SetColor('#444444');
        $b1plot->value->SetFormat('%d €');

        $graph->title->Set("CA des ventes par mois en ".$annee);
        $graph->subtitle->Set("Total HT : ".number_format($totalAnnuel, 0, ',', ' ')." €");
        $graph->title->SetColor('#222222');
        $graph->subtitle->SetColor('#666666');
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
        $theme_class = new UniversalTheme;
        $graph->SetTheme($theme_class);

        //axe des Y
        //$graph->yaxis->SetTickPositions(array(0,30,60,90,120,150,180,210,240,270,300), array(15,45,75,105,135,165,195,225));
        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->ygrid->SetColor('#e8e8e8');
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->xaxis->SetColor('#888888', '#444444');
        $graph->yaxis->SetColor('#888888', '#444444');
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

        // Create the bar plots
        $b1plot = new BarPlot($data1y);
        $b1plot->SetLegend((string) $anneeN);
        $b2plot = new BarPlot($data2y);
        $b2plot->SetLegend((string) $anneeN_1);

        // Create the grouped bar plot
        $gbplot = new GroupBarPlot(array($b2plot,$b1plot));
        // ...and add it to the graPH
        $graph->Add($gbplot);
        $graph->legend->SetPos(0.5,0.95,'center','bottom');
        $graph->legend->SetLayout(LEGEND_HOR);
        $graph->legend->SetFrameWeight(0);
        $graph->legend->SetShadow(false);

        // annee la plus recente en bleu (slot categoriel 1), annee precedente en orange (slot 2)
        $b1plot->SetColor("#2a78d6");
        $b1plot->SetFillColor("#2a78d6");
        $b1plot->value->Show();
        $b1plot->value->SetColor('#444444');
        $b1plot->value->SetFormat('%d €');

        $b2plot->SetColor("#eb6834");
        $b2plot->SetFillColor("#eb6834");
        $b2plot->value->Show();
        $b2plot->value->SetColor('#444444');
        $b2plot->value->SetFormat('%d €');

        $graph->title->Set("Ventes par mois (HT) ".$anneeN_1." / ".$anneeN);
        $graph->title->SetColor('#222222');

        // Display the graph
        $graph->Stroke();
    }

    //?Nombre de COMMANDES payees par mois (une commande = un paiement confirme, quel que soit
    //?le nombre d'articles/lignes qu'elle contient). A ne pas confondre avec le nombre de lignes
    //?de vente affiche dans graphRepartitionTransactionByYear() : une seule commande peut
    //?contenir plusieurs lignes (plusieurs articles differents achetes en une fois).
    public function graphTransactionsByYear($anneeN)
    {
        $totalCommandes = [];

        for($m=1;$m<=12;$m++){
            $paiementsNumber = $this->paymentRepository->findNumberOfPaiements($m,$anneeN);

            array_push($totalCommandes,$paiementsNumber);

        }

        $data1y = $totalCommandes;

        // Create the graph. These two calls are always required
        $graph = new GraphGraph(1050,600,'auto');
        $graph->SetScale("textlin");

        $theme_class = new UniversalTheme;
        $graph->SetTheme($theme_class);

        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->ygrid->SetColor('#e8e8e8');
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->xaxis->SetColor('#888888', '#444444');
        $graph->yaxis->SetColor('#888888', '#444444');
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

        // Create the bar plots
        $b1plot = new BarPlot($data1y);

        // Create the grouped bar plot
        $gbplot = new GroupBarPlot(array($b1plot));
        // ...and add it to the graPH
        $graph->Add($gbplot);
        // une seule serie : le titre porte deja l'annee, pas besoin de legende
        $graph->legend->Hide();

        $b1plot->SetColor("#2a78d6");
        $b1plot->SetFillColor("#2a78d6");
        $b1plot->value->Show();
        $b1plot->value->SetColor('#444444');
        $b1plot->value->SetFormat('%d');

        $graph->title->Set("Nombre de commandes payées par mois en ".$anneeN);
        $graph->subtitle->Set("Total des commandes payées : ".array_sum($totalCommandes));
        $graph->title->SetColor('#222222');
        $graph->subtitle->SetColor('#666666');
        // Display the graph
        $graph->Stroke();
    }

    //?Deux mesures affichees cote a cote pour chaque mois, par categorie (Boites completes /
    //?Occasions / Pieces detachees) :
    //?- quantite = nombre d'unites physiques vendues (SUM(document_line.quantity))
    //?- lignes   = nombre de lignes de document_line, PAS de commandes (cf. graphTransactionsByYear
    //?  qui compte les commandes/paiements). Une seule commande peut contenir plusieurs lignes.
    //?Occasions et boites completes ont toujours quantity = 1 par ligne, donc quantite = lignes
    //?pour elles ; seule la categorie "pieces detachees" peut differer (une ligne de quantite 16
    //?= une seule ligne, mais 16 pieces vendues).
    public function graphRepartitionTransactionByYear($annee)
    {
        $qtePieces = [];
        $qteOccasions = [];
        $qteBoites = [];
        $lignesPieces = [];
        $lignesOccasions = [];
        $lignesBoites = [];

        for($m=1;$m<=12;$m++){
            $paiementsInMonth = $this->paymentRepository->findPaiements($m,$annee);

            $qtePiecesMois = 0;
            $qteOccasionsMois = 0;
            $qteBoitesMois = 0;
            $lignesPiecesMois = 0;
            $lignesOccasionsMois = 0;
            $lignesBoitesMois = 0;

            foreach($paiementsInMonth as $paiement){
                foreach($paiement->getDocument()->getDocumentLines() as $docLine){
                    if($docLine->getItem() != NULL){
                        $qtePiecesMois += $docLine->getQuantity();
                        $lignesPiecesMois += 1;
                    }
                    if($docLine->getOccasion() != NULL){
                        $qteOccasionsMois += $docLine->getQuantity();
                        $lignesOccasionsMois += 1;
                    }
                    if($docLine->getBoite() != NULL){
                        $qteBoitesMois += $docLine->getQuantity();
                        $lignesBoitesMois += 1;
                    }
                }
            }

            $qtePieces[] = $qtePiecesMois;
            $qteOccasions[] = $qteOccasionsMois;
            $qteBoites[] = $qteBoitesMois;
            $lignesPieces[] = $lignesPiecesMois;
            $lignesOccasions[] = $lignesOccasionsMois;
            $lignesBoites[] = $lignesBoitesMois;
        }

        $totalUnites = array_sum($qtePieces) + array_sum($qteOccasions) + array_sum($qteBoites);
        $totalLignes = array_sum($lignesPieces) + array_sum($lignesOccasions) + array_sum($lignesBoites);

        // Create the graph. These two calls are always required
        $graph = new GraphGraph(1050,600,'auto');
        $graph->SetScale("textlin");

        $theme_class = new UniversalTheme;
        $graph->SetTheme($theme_class);

        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->ygrid->SetColor('#e8e8e8');
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->xaxis->SetColor('#888888', '#444444');
        $graph->yaxis->SetColor('#888888', '#444444');
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

        // Deux empilements cote a cote par mois : quantite (barres pleines) puis nombre de
        // ventes (barres hachurees), meme couleur par categorie dans les deux (palette
        // categorielle, ordre fixe jamais cycle). La legende n'est definie que sur
        // l'empilement "quantite" pour ne pas dupliquer les 3 entrees.
        $q1 = new BarPlot($qteBoites);
        $q1->SetColor('#2a78d6');
        $q1->SetFillColor('#2a78d6');
        $q1->value->SetFont(FF_ARIAL,FS_BOLD);
        $q1->value->Show();
        $q1->value->SetColor('#444444');
        $q1->value->SetFormat('%d');
        $q1->SetLegend('Boîtes complètes');

        $q2 = new BarPlot($qteOccasions);
        $q2->SetColor('#eb6834');
        $q2->SetFillColor('#eb6834');
        $q2->value->Show();
        $q2->value->SetColor('#444444');
        $q2->value->SetFormat('%d');
        $q2->SetLegend('Occasions');

        $q3 = new BarPlot($qtePieces);
        $q3->SetColor('#1baf7a');
        $q3->SetFillColor('#1baf7a');
        $q3->value->Show();
        $q3->value->SetColor('#444444');
        $q3->value->SetFormat('%d');
        $q3->SetLegend('Pièces détachées');

        $stackQte = new AccBarPlot(array($q1,$q2,$q3));

        $v1 = new BarPlot($lignesBoites);
        $v1->SetColor('#2a78d6');
        $v1->SetFillColor('#2a78d6');
        $v1->SetPattern(BAND_RDIAG, '#ffffff');
        $v1->value->Show();
        $v1->value->SetColor('#444444');
        $v1->value->SetFormat('%d');

        $v2 = new BarPlot($lignesOccasions);
        $v2->SetColor('#eb6834');
        $v2->SetFillColor('#eb6834');
        $v2->SetPattern(BAND_RDIAG, '#ffffff');
        $v2->value->Show();
        $v2->value->SetColor('#444444');
        $v2->value->SetFormat('%d');

        $v3 = new BarPlot($lignesPieces);
        $v3->SetColor('#1baf7a');
        $v3->SetFillColor('#1baf7a');
        $v3->SetPattern(BAND_RDIAG, '#ffffff');
        $v3->value->Show();
        $v3->value->SetColor('#444444');
        $v3->value->SetFormat('%d');

        $stackVentes = new AccBarPlot(array($v1,$v2,$v3));

        $gbplot = new GroupBarPlot(array($stackQte, $stackVentes));

        // ...and add it to the graPH
        $graph->Add($gbplot);
        $graph->legend->SetPos(0.5,0.95,'center','bottom');
        $graph->legend->SetLayout(LEGEND_HOR);
        $graph->legend->SetFrameWeight(0);
        $graph->legend->SetShadow(false);

        $graph->title->Set("Répartition des ventes par mois en ".$annee);
        $graph->subtitle->Set(
            "Total unités vendues : ".number_format($totalUnites, 0, ',', ' ')
            ." | Total lignes vendues : ".number_format($totalLignes, 0, ',', ' ')."\n"
            ."Barres pleines (gauche) = quantité vendue (unités) — barres hachurées (droite) = nombre de lignes (≠ commandes : une commande peut contenir plusieurs lignes)"
        );
        $graph->title->SetColor('#222222');
        $graph->subtitle->SetColor('#666666');

        // Display the graph
        $graph->Stroke();
    }

    //?Synthese globale par mois : nombre de commandes (paiements confirmes), nombre de lignes de
    //?vente et nombre d'unites vendues, toutes categories confondues (pieces detachees + occasions
    //?+ boites completes). Les 3 colonnes sont volontairement cote a cote (pas empilees) car elles
    //?ne s'additionnent pas entre elles - c'est la meme activite vue a trois echelles differentes
    //?(commandes <= lignes <= unites, cf. commentaire de vocabulaire en tete de fichier).
    public function graphSyntheseVentesByYear($annee)
    {
        $commandes = [];
        $lignes = [];
        $unites = [];
        $ca = [];

        for($m=1;$m<=12;$m++){
            $paiementsInMonth = $this->paymentRepository->findPaiements($m,$annee);

            $lignesMois = 0;
            $unitesMois = 0;

            foreach($paiementsInMonth as $paiement){
                foreach($paiement->getDocument()->getDocumentLines() as $docLine){
                    if($docLine->getItem() != NULL || $docLine->getOccasion() != NULL || $docLine->getBoite() != NULL){
                        $lignesMois += 1;
                        $unitesMois += $docLine->getQuantity();
                    }
                }
            }

            $commandes[] = count($paiementsInMonth);
            $lignes[] = $lignesMois;
            $unites[] = $unitesMois;
            $ca[] = intval(number_format($this->paymentRepository->findPaiementsAndReturnCA($m,$annee) / 100, 2));
        }

        $totalCommandes = array_sum($commandes);
        $totalLignes = array_sum($lignes);
        $totalUnites = array_sum($unites);
        $totalCA = array_sum($ca);

        // Create the graph. These two calls are always required
        $graph = new GraphGraph(1050,600,'auto');
        $graph->SetScale("textlin");

        $theme_class = new UniversalTheme;
        $graph->SetTheme($theme_class);

        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->ygrid->SetColor('#e8e8e8');
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->xaxis->SetColor('#888888', '#444444');
        $graph->yaxis->SetColor('#888888', '#444444');
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

        // 3 colonnes par mois, cote a cote (palette categorielle, ordre fixe jamais cycle)
        $b1plot = new BarPlot($commandes);
        $b1plot->SetColor('#2a78d6');
        $b1plot->SetFillColor('#2a78d6');
        $b1plot->value->SetFont(FF_ARIAL,FS_BOLD);
        $b1plot->value->Show();
        $b1plot->value->SetColor('#444444');
        $b1plot->value->SetFormat('%d');
        $b1plot->SetLegend('Commandes payées');

        $b2plot = new BarPlot($lignes);
        $b2plot->SetColor('#eb6834');
        $b2plot->SetFillColor('#eb6834');
        $b2plot->value->Show();
        $b2plot->value->SetColor('#444444');
        $b2plot->value->SetFormat('%d');
        $b2plot->SetLegend('Lignes vendues');

        $b3plot = new BarPlot($unites);
        $b3plot->SetColor('#1baf7a');
        $b3plot->SetFillColor('#1baf7a');
        $b3plot->value->Show();
        $b3plot->value->SetColor('#444444');
        $b3plot->value->SetFormat('%d');
        $b3plot->SetLegend('Unités vendues');

        $gbplot = new GroupBarPlot(array($b1plot,$b2plot,$b3plot));

        // ...and add it to the graPH
        $graph->Add($gbplot);

        // CA en 4eme serie, sur un axe Y de droite independant (echelle en euros, pas en
        // nombre) - evite de melanger des unites incompatibles sur le meme axe.
        $y2Max = max(1, max($ca) * 1.15);
        $graph->SetY2Scale('lin', 0, $y2Max);
        $graph->y2axis->title->Set('CA (€)');
        $graph->y2axis->title->SetColor('#a020f0');
        $graph->y2axis->SetColor('#a020f0', '#a020f0');
        // La marge droite par defaut (30px) est trop etroite pour les libelles + le titre de
        // l'axe Y2 (ex: "1500" coupe en "150") - on l'agrandit, sans toucher aux autres marges
        // deja calculees par le theme.
        $graph->SetMargin($graph->img->left_margin, 90, $graph->img->top_margin, $graph->img->bottom_margin);

        $caLine = new LinePlot($ca);
        $caLine->SetColor('#a020f0');
        $caLine->SetWeight(3);
        $caLine->mark->SetType(MARK_FILLEDCIRCLE);
        $caLine->mark->SetColor('#a020f0');
        $caLine->mark->SetFillColor('#a020f0');
        $caLine->mark->SetWidth(4);
        $caLine->SetLegend('CA (€, axe droite)');
        $graph->AddY2($caLine);

        $graph->legend->SetPos(0.5,0.95,'center','bottom');
        $graph->legend->SetLayout(LEGEND_HOR);
        $graph->legend->SetFrameWeight(0);
        $graph->legend->SetShadow(false);

        $graph->title->Set("Synthèse des ventes et du CA par mois en ".$annee);
        $graph->subtitle->Set(
            "Total : ".number_format($totalCommandes, 0, ',', ' ')." commandes, "
            .number_format($totalLignes, 0, ',', ' ')." lignes, "
            .number_format($totalUnites, 0, ',', ' ')." unités vendues, "
            .number_format($totalCA, 0, ',', ' ')." € HT\n"
            ."Commande = 1 paiement confirmé (ex: 33) — Ligne = 1 article distinct acheté, une commande peut en contenir plusieurs (ex: 105)\n"
            ."Unité = quantité totale vendue, une ligne peut à elle seule en valoir plusieurs (ex: 207) — toujours : commandes ≤ lignes ≤ unités\n"
            ."La courbe CA (violet) utilise l'échelle de DROITE, en euros — indépendante des 3 colonnes (échelle de gauche, en nombre)"
        );
        $graph->title->SetColor('#222222');
        $graph->subtitle->SetColor('#666666');

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

        $theme_class = new UniversalTheme;
        $graph->SetTheme($theme_class);

        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->ygrid->SetColor('#e8e8e8');
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->xaxis->SetColor('#888888', '#444444');
        $graph->yaxis->SetColor('#888888', '#444444');
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

        // Create the bar plots
        $b1plot = new BarPlot($data1y);

        // Create the grouped bar plot
        $gbplot = new GroupBarPlot(array($b1plot));
        // ...and add it to the graPH
        $graph->Add($gbplot);
        // une seule serie : le titre porte deja l'annee, pas besoin de legende
        $graph->legend->Hide();

        $b1plot->SetColor("#2a78d6");
        $b1plot->SetFillColor("#2a78d6");
        $b1plot->value->Show();
        $b1plot->value->SetColor('#444444');
        $b1plot->value->SetFormat('%d');

        $graph->title->Set("Inscriptions par mois en ".$annee);
        $graph->subtitle->Set("Total des inscrits : ".array_sum($total));
        $graph->title->SetColor('#222222');
        $graph->subtitle->SetColor('#666666');

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
                        // Un article peut avoir plusieurs boites d'origine (piece partagee entre
                        // plusieurs editions) : on ne retient que la premiere, par choix.
                        $boiteOrigine = $docLine->getItem()->getBoiteOrigine()->first();
                        if($boiteOrigine && !in_array($boiteOrigine->getId(), $boitesCompletedWithItems)){
                            $boitesCompletedWithItems[] = $boiteOrigine->getId();
                            $items += 1;
                        }
                    }
                    if($docLine->getBoite() != NULL){
                        if(!in_array($docLine->getBoite()->getId(), $boitesCompletesWithBoites)){
                            $boitesCompletesWithBoites[] = $docLine->getBoite()->getId();
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

        $theme_class = new UniversalTheme;
        $graph->SetTheme($theme_class);

        $graph->yaxis->SetTextTickInterval(1,2);
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->ygrid->SetColor('#e8e8e8');
        $graph->xaxis->SetTickLabels(array('Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre'));
        $graph->xaxis->SetColor('#888888', '#444444');
        $graph->yaxis->SetColor('#888888', '#444444');
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false,false);

        // Create the bar plots
        $b1plot = new BarPlot($totalTransactionByMonthByColumn);
        $b1plot->SetColor('#2a78d6');
        $b1plot->SetFillColor('#2a78d6');
        $b1plot->value->SetFont(FF_ARIAL,FS_BOLD);
        $b1plot->value->Show();
        $b1plot->value->SetColor('#444444');
        $b1plot->value->SetFormat('%d');

        // ...and add it to the graPH
        $graph->Add($b1plot);
        // une seule serie : le titre porte deja l'annee, pas besoin de legende
        $graph->legend->Hide();

        $graph->title->Set("Nombre de jeux complétés sur ".$annee);
        $graph->subtitle->Set("Total des jeux complétés : ".$totalAnnuel);
        $graph->title->SetColor('#222222');
        $graph->subtitle->SetColor('#666666');

        // Display the graph
        $graph->Stroke();
    }
}