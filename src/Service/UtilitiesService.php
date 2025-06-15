<?php

namespace App\Service;

use App\Repository\DocumentLineRepository;
use App\Repository\OccasionRepository;
use App\Repository\PanierRepository;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class UtilitiesService
{
    public function __construct(
        private DocumentLineRepository $documentLineRepository,
        private PanierRepository $panierRepository,
        private RequestStack $requestStack,
        private Security $security,
        private OccasionRepository $occasionRepository
    )
    {
    }

    public function getDateTimeImmutableFromTimestamp($timestamp)
    {
        $tps = (int) $timestamp;
        $date = new DateTimeImmutable();

        if($timestamp !== null){
            return $date->setTimestamp($tps);
        }else{
            return null;
        }
    }

    public function generateRandomString($length = 250, $characters = '0123456789abcdefghijklmnopqrstuvwxyz@!_ABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $charactersLength = strlen($characters);
        $randomString = "";
        for($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength-1)];
        }
        return $randomString;
    }

    public function generateRandomHtmlColor($length = 6, $characters = '0123456789')
    {
        $charactersLength = strlen($characters);
        $randomString = "";
        for($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength-1)];
        }
        return $randomString;
    }

    public function calculTauxTva($taux)
    {

        return ($taux + 100) / 100;

    }

    public function prixTtcToCentsHt($ttc,$taux)
    {

        $tauxTva = $this->calculTauxTva($taux);

        $ht = round($ttc * 100 / $tauxTva,2);

        return $ht;
    }

    public function stringToNull($value)
    {

        if($value == 'NULL' ){
            $value = NULL;
        }

        return $value;
    }

    public function htToTTC($htInCents,$taxValue)
    {
        return $htInCents * (1 + ($taxValue / 100));
    }

    public function totauxByPanierGroup($categories)
    {

        $totaux = [];
        $price = 0;
        $weigth = 0;


        foreach($categories as $category){

            //en fonction du calcul voulu des articles ou occasions
            if(!empty($category->getOccasion())){

                $weigth += $category->getOccasion()->getBoite()->getWeigth(); 

            }else if(!empty($category->getItem())){
                
                $weigth += $category->getItem()->getWeigth() * $category->getQte();  
            }

            $price += $category->getPriceWithoutTax();
        }
        

        $totaux['price'] = $price;
        $totaux['weigth'] = $weigth;

        return $totaux;
    }

    public function totauxcategorysImportV2($categorys)
    {

        $totaux = [];
        $price = 0;
        $weigth = 0;

        foreach($categorys as $category){

            if(is_null($category->getBoite())){
                
                $boiteWeigth = 0;

            }else{

                $boiteWeigth = $category->getBoite()->getWeigth();
            }

            $weigth += $boiteWeigth * $category->getQuantity();  
            $price += $category->getPriceExcludingTax();
        }

        $totaux['price'] = $price;
        $totaux['weigth'] = $weigth;

        return $totaux;
    }

    public function totauxOccasionsImportV2($categorys)
    {

        $totaux = [];
        $price = 0;
        $weigth = 0;

        foreach($categorys as $category){

            $weigth += $category->getOccasion()->getBoite()->getWeigth() * $category->getQuantity();  
            $price += $category->getPriceExcludingTax();
        }

        $totaux['price'] = $price;
        $totaux['weigth'] = $weigth;

        return $totaux;
    }

    public function totauxBoitesImportV2($categorys)
    {

        $totaux = [];
        $price = 0;
        $weigth = 0;

        foreach($categorys as $category){

            $weigth += $category->getBoite()->getWeigth() * $category->getQuantity();  
            $price += $category->getPriceExcludingTax();
        }

        $totaux['price'] = $price;
        $totaux['weigth'] = $weigth;

        return $totaux;
    }

    public function easyAdminLogicWhenBilling(RequestStack $requestStack)
    {
        ///?on recupere l'id de l'occasion
        $id = $requestStack->getCurrentRequest()->get('entityId');
        //de base on set que l'on peut modifier
        $disabledAfterBilling = false;

        if($id){
            $occasion = $this->occasionRepository->find($id);
            $documentLine = $this->documentLineRepository->findOneBy(['occasion' => $occasion]);

            if($occasion AND $occasion->getOffSiteOccasionSale() != null OR $documentLine){
                $disabledAfterBilling = true;
            }

        }

        return $disabledAfterBilling;
    }

    public function generateAccountNumber(int $id)
    {

        $accountTag = 'C';

        $accountTagLength = 5;
        $idLength = strlen($id);
        $zeros = str_repeat("0", $accountTagLength - $idLength);

        return $accountTag.$zeros.$id;

    }

}