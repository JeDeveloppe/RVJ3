<?php

namespace App\Service;

use App\Entity\MeansOfPayement;
use App\Repository\MeansOfPayementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MeansOfPayementService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MeansOfPayementRepository $meansOfPayementRepository
        ){
    }

    public function addMoyens(SymfonyStyle $io)
    {

        $moyens = ['CB','ESPÈCES','CHQ','VIR','DON','INCONNU','EN COURS'];

        foreach($moyens as $moyenArray){

            $moyen = $this->meansOfPayementRepository->findOneBy(['name' => $moyenArray]);

            if(!$moyen){
                $moyen = new MeansOfPayement();
            }

            $moyen->setName($moyenArray);
            $this->em->persist($moyen);

        }
        $this->em->flush();
        $io->success('Créations terminée');
    }
}