<?php

namespace App\Service;

use App\Entity\Ambassador;
use League\Csv\Reader;
use App\Repository\AmbassadorRepository;
use App\Repository\CityRepository;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AmbassadorService
{
    public function __construct(
        private AmbassadorRepository $ambassadorRepository,
        private EntityManagerInterface $em,
        private CityRepository $cityRepository,
        private DepartmentRepository $departmentRepository,
        private UtilitiesService $utilitiesService
        ){
    }

    public function constructionMapOfFranceWithAmbassadors($baseUrl, array $ambassadors)
    {

        $stores = []; //? toutes les réponses seront dans ce tableau final

        foreach($ambassadors as $ambassador)
        {

            if($ambassador->getOrganization() == NULL){

                $name = '';

            }else{

                $name = $ambassador->getOrganization().' <br/>';
            }

            $nameAdress = $ambassador->getLastname().' '.$ambassador->getFirstname().'<br/>'.$ambassador->getStreet().'<br/>';

            if($ambassador->getDescription() == NULL || $ambassador->getDescription() == ''){

                $description_detail = '';

            }else{

                $description_detail = '<p style="margin-top:10px; padding:10px; width:100%; text-align:justify;">'.$ambassador->getDescription().'</p>';
            }
            
            if($ambassador->getFullurl() == NULL || $ambassador->getFullurl() == ''){

                $url = '';

            }else{

                $url = $ambassador->getFullurl();
            }

            if($ambassador->getPhone() == NULL || $ambassador->getPhone() == ''){

                $phone = '';

            }else{

                $phone = '<i class="fa-solid fa-phone"></i> : '.$ambassador->getPhone().'<br/>';
            }

            if($ambassador->getEmail() == NULL || $ambassador->getEmail() == ''){

                $email = '';

            }else{

                $email = '<i class="fa-solid fa-envelope"></i> : '.$ambassador->getEmail().'<br/>';
            }

            if($ambassador->getFacebookLink() == NULL || $ambassador->getFacebookLink() == ''){

                $facebook = '';

            }else{

                $facebook = '<i class="fa-brands fa-facebook"></i> :<a href="'.$ambassador->getFacebookLink().'">Lien vers Facebook</a><br/>';
            }

            if($ambassador->getInstagramLink() == NULL || $ambassador->getInstagramLink() == ''){

                $instagram = '';

            }else{

                $instagram = '<i class="fa-brands fa-instagram"></i> :<a href="'.$ambassador->getInstagramLink().'">Lien vers Instagram</a><br/>';
            }

            $description = $nameAdress;
            $description .= $description_detail;
            $description .= '<p>';
            $description .= $phone;
            $description .= $email;
            $description .= $facebook;
            $description .= $instagram;
            $description .= '</p>';

            $lat = $ambassador->getLatitude();
            $long = $ambassador->getLongitude();
            if($lat == NULL){
                $lat = $ambassador->getCity()->getLatitude();
            }
            if($long == NULL){
                $long = $ambassador->getCity()->getLongitude();
            }

            $stores[] = 
            [
                "lat" => $lat,
                "lng" => $long,
                "color" => "#1DBA9D",
                "name" => $name.$ambassador->getCity()->getName().' ('.$ambassador->getCity()->getDepartment()->getName().')',
                "description" => $description,
                "url" => $url,
                "size" => 40,
            ];
        }

        $jsonStores = json_encode($stores, JSON_FORCE_OBJECT); 

        $donnees = $jsonStores;

        return $donnees;
    }

    public function importAmbassadors(SymfonyStyle $io): void
    {
        $io->title('Importation des ambassadeurs');

        $ambassadors = $this->readCsvFileAmbassadors();
        
        $io->progressStart(count($ambassadors));

        foreach($ambassadors as $arrayAmbassador){
            $io->progressAdvance();
            $ambassador = $this->createOrUpdateClient($arrayAmbassador);
            $this->em->persist($ambassador);
        }

        $this->em->flush();

        $io->progressFinish();
        $io->success('Importation terminée');
    }

    //lecture des fichiers exportes dans le dossier import
    private function readCsvFileAmbassadors(): Reader
    {
        $csvAmbassadeurs = Reader::createFromPath('%kernel.root.dir%/../import/ambassadeurs.csv','r');
        $csvAmbassadeurs->setHeaderOffset(0);

        return $csvAmbassadeurs;
    }

    private function createOrUpdateClient(array $arrayAmbassador): Ambassador
    {
        $ambassador = $this->ambassadorRepository->findOneBy(['privateemail' => $arrayAmbassador['Email']]);

        if(!$ambassador){
            $ambassador = new Ambassador();
        }

        $ambassador->setPrivateemail($arrayAmbassador['Email'])
            ->setPrivatefirstname($arrayAmbassador['Prenom'])
            ->setPrivatelastname($arrayAmbassador['Nom'])
            ->setPrivatephone($arrayAmbassador['Phone'])
            ->setPrivatestreet("A compléter")
            ->setPrivatecity($this->cityRepository->findOneBy(['name' => str_replace(' ','-',$arrayAmbassador['Ville'])])  ?? $this->cityRepository->findOneBy(['name' => 'Caen']))
            ->setOnTheCarte(false)
            ->setColisSend((int)$arrayAmbassador['NbColis'] ?? 0);

        if($arrayAmbassador['PtCollecte'] == 1){
            $ambassador->setEmail($arrayAmbassador['Email'])
            ->setOrganization($this->utilitiesService->stringToNull($arrayAmbassador['Structure']))
            ->setFirstname($arrayAmbassador['Prenom'])
            ->setLastname($arrayAmbassador['Nom'])
            ->setPhone($arrayAmbassador['Phone'])
            ->setStreet("A compléter")
            ->setCity($this->cityRepository->findOneBy(['name' => str_replace(' ','-',$arrayAmbassador['Ville'])])  ?? $this->cityRepository->findOneBy(['name' => 'Caen']))
            ->setOnTheCarte(true);
        }

        return $ambassador;
    }
}