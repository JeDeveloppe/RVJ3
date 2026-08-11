<?php

namespace App\Service;

use App\Entity\SiteSetting;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SiteSettingRepository;
use Symfony\Component\Console\Style\SymfonyStyle;

class SiteSettingsService
{
    public function __construct(
        private SiteSettingRepository $siteSettingRepository,
        private EntityManagerInterface $em
        ){
    }

    public function addSettings(SymfonyStyle $io){

        $io->title('Importation / mise à jour des settings du site');

        $siteSettings = $this->siteSettingRepository->find(1);

        if(!$siteSettings){
            $siteSettings = new SiteSetting();
        }

        $siteSettings->setBlockEmailSending(true)->setMarquee(null)->setDistanceMaxForOccasionBuy(15);
        $this->em->persist($siteSettings);
        $this->em->flush();

        $io->success('Terminé !');
    }
}