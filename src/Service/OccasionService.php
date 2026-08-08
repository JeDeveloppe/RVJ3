<?php

namespace App\Service;

use League\Csv\Reader;
use App\Entity\Occasion;
use App\Entity\MovementOccasion;
use App\Entity\ConditionOccasion;
use App\Repository\BoiteRepository;
use App\Repository\OccasionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\DurationOfGameRepository;
use App\Repository\MovementOccasionRepository;
use App\Repository\NumbersOfPlayersRepository;
use App\Repository\ConditionOccasionRepository;
use App\Repository\OffSiteOccasionSaleRepository;
use App\Repository\StockRepository;
use Symfony\Component\Console\Style\SymfonyStyle;

class OccasionService
{
    public function __construct(
        private OccasionRepository $occasionRepository,
        private ConditionOccasionRepository $conditionOccasionRepository,
        private MovementOccasionRepository $movementOccasionRepository,
        private EntityManagerInterface $em,
        private OffSiteOccasionSaleRepository $offSiteOccasionSaleRepository,
        private BoiteRepository $boiteRepository,
        private UtilitiesService $utilitiesService,
        private DurationOfGameRepository $durationOfGameRepository,
        private NumbersOfPlayersRepository $numbersOfPlayersRepository,
        private StockRepository $stockRepository
    )
    {
    }

    public function addConditions(SymfonyStyle $io)
    {

        $green = '#00BC9D';
        $orange = '#FDC448';
        $red = '#E84798';

        $conditions = [
            [
                'name' => 'Bon',
                'color' => $green,
                'discount' => 0
            ],
            [
                'name' => 'Moyen',
                'color' => $orange,
                'discount' => 100
            ],
            [
                'name' => 'Originale',
                'color' => $green,
                'discount' => 0
            ],
            [
                'name' => 'Sur la boite',
                'color' => $green,
                'discount' => 0
            ],
            [
                'name' => 'Imprimée',
                'color' => $green,
                'discount' => 50
            ],
            [
                'name' => 'Comme neuf',
                'color' => $green,
                'discount' => 0
            ],
            [
                'name' => 'Sans',
                'color' => $red,
                'discount' => 100
            ],
            [
                'name' => 'Neuf',
                'color' => $green,
                'discount' => 0
            ],
            [
                'name' => 'Neuve',
                'color' => $green,
                'discount' => 0
            ]
            ];

        $ventesDons = ['DON','VENTE','INCONNU'];

        $io->title('Création: condition des occasion et des moyens de paiement');

        foreach($conditions as $conditionArray){

            $condition = $this->conditionOccasionRepository->findOneBy(['name' => $conditionArray]);

            if(!$condition){
                $condition = new ConditionOccasion();
            }

            $condition->setName($conditionArray['name'])->setColor($conditionArray['color'])->setDiscount($conditionArray['discount']);
            $this->em->persist($condition);

        }

        foreach($ventesDons as $venteDon){

            $move = $this->movementOccasionRepository->findOneBy(['name' => $venteDon]);

            if(!$move){
                $move = new MovementOccasion();
            }

            $move->setName($venteDon);
            $this->em->persist($move);
        }

        $this->em->flush();

    }

    public function updateOccasionMouvement(SymfonyStyle $io): void
    {
    
        $io->title('Mise à jour mouvements / occasions');

        $offSiteOccasionSales = $this->offSiteOccasionSaleRepository->findAll();
        
        $io->progressStart(count($offSiteOccasionSales));

        foreach($offSiteOccasionSales as $offSiteOccasionSale){
            $io->progressAdvance();

            $occasion = $this->occasionRepository->find($offSiteOccasionSale->getOccasion());
            $occasion->setOffSiteOccasionSale($offSiteOccasionSale);
            $this->em->persist($occasion);
        }

        $this->em->flush();

        $io->progressFinish();
        $io->success('Mise à jour terminée');
    }

    public function importOccasions(SymfonyStyle $io): void
    {
        $io->title('Importation des occasions');

        $occasions = $this->readCsvFileJeuxComplets();
        
        $io->progressStart(count($occasions));

        foreach($occasions as $arrayOccasion){
            $io->progressAdvance();
            $this->createOrUpdateOccasion($arrayOccasion);
        }
        
        $this->em->flush();

        unset($occasions);
        $io->progressFinish();
        $io->success('Importation terminée');

    }

    //lecture des fichiers exportes dans le dossier import
    private function readCsvFileJeuxComplets(): Reader
    {
        $csvOccasions = Reader::createFromPath('%kernel.root.dir%/../import/_table_jeux_complets.csv','r');
        $csvOccasions->setHeaderOffset(0);

        return $csvOccasions;
    }

    private function createOrUpdateOccasion(array $arrayOccasion): void
    {
        $occasion = $this->occasionRepository->findOneBy(['rvj2id' => $arrayOccasion['idJeuxComplet']]);

        if(!$occasion){
            $occasion = new Occasion();
        }

        $boxCondition = $this->cleanBoxConditionAndEtatMateriel($arrayOccasion['etatBoite']);
        $materielEtat = $this->cleanBoxConditionAndEtatMateriel($arrayOccasion['etatMateriel']);
        $etatRegle = $this->cleanEtatRegle($arrayOccasion['regleJeu']);

        $occasion->setBoite($this->boiteRepository->findOneBy(['id' => $arrayOccasion['idCatalogue']]) ?? $this->boiteRepository->findOneBy(['name' => 'BOITE SUPPRIMEE']))
                ->setReference($arrayOccasion['reference'])
                ->setInformation($this->utilitiesService->stringToNull($arrayOccasion['information']))
                ->setIsNew($this->stringToBoolean($arrayOccasion['isNeuf']))
                ->setBoxCondition($this->conditionOccasionRepository->findOneBy(['name' => $boxCondition]))
                ->setEquipmentCondition($this->conditionOccasionRepository->findOneBy(['name' => $materielEtat]))
                ->setGameRule($this->conditionOccasionRepository->findOneBy(['name' => $etatRegle]))
                ->setIsOnLine($arrayOccasion['actif'])
                ->setOffSiteOccasionSale(null)
                ->setPriceWithoutTax($arrayOccasion['prixHT'])
                ->setRvj2id($arrayOccasion['idJeuxComplet'])
                ->setStock($this->stockRepository->findOneBy(['name' => $_ENV['STOCK_NAME_DEFAULT']]))
                ;

                $this->em->persist($occasion);

    }

    private function stringToBoolean($value)
    {
        
        if($value = "NULL"){
            
            $value = 0;

        }else{

            $value = 1;
        }

        return $value;
    }

    public function returnOptionsForFormAndTitleForOccasionCatalogByCategory(?string $category = null):array
    {

        switch($category){
            case 'jeux-pour-enfants': //? pareil que la navbar et le footer
                $agesByCategory = [
                    'start' => 2,
                    'end' => 6
                ];
                $twig = [
                    'title' => 'pour enfants',
                    'titleH1' => 'Jeux pour enfants',
                    'breadcrumb' => '<span class="text-purple">Tous les jeux</span> > Enfants'
                ];
                break;
            case 'jeux-pour-initie-es': //? pareil que la navbar et le footer
                $agesByCategory = [
                    'start' => 12,
                    'end' => 12
                ];
                $twig = [
                    'title' => 'pour initié·es',
                    'titleH1' => 'Jeux pour initié·es',
                    'breadcrumb' => '<span class="text-purple">Tous les jeux</span> > Initié·es'
                ];
                break;
            case 'jeux-tout-public': //? pareil que la navbar et le footer
                $agesByCategory = [
                    'start' => 7,
                    'end' => 11
                ];
                $twig = [
                    'title' => 'tout public',
                    'titleH1' => 'Jeux tout public',
                    'breadcrumb' => '<span class="text-purple">Tous les jeux</span> > Tout public'
                ];
                break;
            default:
                $agesByCategory = [
                    'start' => 2,
                    'end' => 12
                ];
                $twig = [
                    'title' => '',
                    'titleH1' => 'Tous les jeux',
                    'breadcrumb' => ''
                ];
                break;
        }

        //choix du form occasion
        $choices = [];

        $ageChoices = [];
        for($i = $agesByCategory['start']; $i <= $agesByCategory['end']; $i++){
            $an = ' an';
            if($i > 1){
                $an = ' ans';
            }
            $text = 'Dès ';
            if($i == 12){
                $text = 'Plus de ';
            }
            $ageChoices[$text.$i.$an] = $i;
            $agesForSearch[] = $i;
        }

        $durations = [];
        $durationsChecked = $this->durationOfGameRepository->findBy(['displayInForm' => true],['orderOfAppearance' => 'ASC']);
        foreach($durationsChecked as $durationChecked){
            $durations[$durationChecked->getName()] = $durationChecked->getName();
        }

        $playerChoices = [];
        $playersChecked = $this->numbersOfPlayersRepository->findBy(['isInOccasionFormSearch' => true],['orderOfAppearance' => 'ASC']);
        foreach($playersChecked as $playerChecked){
            $playerChoices[$playerChecked->getKeyword()] = $playerChecked->getName();
        }

        $choices['ages_options_for_form'] = $ageChoices;
        $choices['durations_options_for_form'] = $durations;
        $choices['players_options_for_form'] = $playerChoices;
        $choices['players_in_database'] = $this->numbersOfPlayersRepository->findAll();
        $choices['start_and_end_ages'] = $agesByCategory;
        $choices['twig'] = $twig;

        return $choices;
    }

    public function cleanBoxConditionAndEtatMateriel($etat){

        $etat = str_replace('ÉTAT','',$etat);
        $etat = str_replace(' ','',$etat);
        if(str_contains($etat, 'NEUF')){
            $etat = 'Comme neuf';
        }
        $etat = strtolower($etat);
        $etat = ucfirst($etat);

        return $etat;
    }

    public function cleanEtatRegle($etat){

        $etat = str_replace('ÉTAT','',$etat);
        $etat = str_replace(' ','',$etat);
        if(str_contains($etat, 'BOITE')){
            $etat = 'Sur la boite';
        }
        if(str_contains($etat, 'IMPRIMÉE')){
            $etat = 'Imprimée';
        }
        $etat = strtolower($etat);
        $etat = ucfirst($etat);

        return $etat;
    }
}