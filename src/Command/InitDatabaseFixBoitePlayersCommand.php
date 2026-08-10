<?php

namespace App\Command;

use App\Service\AmbassadorService;
use App\Service\BoiteService;
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

#[AsCommand(name: 'app:init-database:fix-boite-players')]

class InitDatabaseFixBoitePlayersCommand extends Command
{
    public function __construct(
        private BoiteService $boiteService,
        )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        // ini_set('memory_limit', '2048M');
        ini_set("memory_limit", -1);

        $io = new SymfonyStyle($input,$output);
        
        //on met a jour le nombre de joueur / boite
        $this->boiteService->updateBoiteWithNewPlayers($io);

        return Command::SUCCESS;
    }

}