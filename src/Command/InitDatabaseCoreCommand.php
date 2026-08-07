<?php

namespace App\Command;

use App\Service\AmbassadorService;
use App\Service\CityService;
use App\Service\CountryService;
use App\Service\DepartmentService;
use App\Service\DocumentParametreService;
use App\Service\GranderegionService;
use App\Service\LevelService;
use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:init-database:core')]

class InitDatabaseCoreCommand extends Command
{
    public function __construct(
            private UserService $userService,
            private CountryService $countryService,
            private DocumentParametreService $documentParametreService,
            private DepartmentService $departmentService,
            private CityService $cityService,
            private LevelService $levelService,
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
        
        //creation PAYS name/isocode
        $this->countryService->addCountries();
        
        //ON CREE OU ON MET A JOUR L'ADMIN
        $this->levelService->addLevels($io);
        $this->userService->initForProd_adminUser($io);

        //ON INJECTE les parametres des documents
        $this->documentParametreService->initDocumentParametre($io);

        //on importe les clients
        $this->userService->importClients($io);

        //on importe les regions / departements
        $this->granderegionService->importRegionsFrancaise($io);
        $this->departmentService->importDepartementsFrancais($io);

        return Command::SUCCESS;
    }

}