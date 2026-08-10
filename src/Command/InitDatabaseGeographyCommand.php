<?php

namespace App\Command;

use App\Service\AmbassadorService;
use App\Service\CityService;
use App\Service\CountryService;
use App\Service\DepartmentService;
use App\Service\DocumentParametreService;
use App\Service\GranderegionService;
use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:init-database:geography')]

class InitDatabaseGeographyCommand extends Command
{
    public function __construct(
            private UserService $userService,
            private CountryService $countryService,
            private DocumentParametreService $documentParametreService,
            private DepartmentService $departmentService,
            private CityService $cityService,
            private GranderegionService $granderegionService,
            private AmbassadorService $ambassadorService
        )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        // ini_set('memory_limit', '2048M');
        ini_set("memory_limit", -1);

        $io = new SymfonyStyle($input,$output);
        
        //on injecte les villes de france
        $this->cityService->importCitiesOfFrance($io);

        //on injecte les ambassadeurs
        $this->ambassadorService->importAmbassadors($io);

        //on importe les regions / departements / villes BELGE
        $this->granderegionService->importRegionsBelge($io);
        $this->departmentService->importDepartementsBelge($io);
        $this->cityService->importCitiesOfBelgique($io);

        return Command::SUCCESS;
    }

}