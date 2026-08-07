<?php

namespace App\Service;

class CatalogueService
{
    public function addNumberOfItemWithStockNotNull(array $donneesFromDatabases):array{
        //calcul par boite du nombre d'articles en stock
        foreach($donneesFromDatabases as $boite){
            $count = 0;
            foreach($boite->getItemsOrigine() as $item){
                if($item->getStockForSale() > 0){
                    $count++;
                }
                $boite->setNumberOfItemWithStockNotNull($count);
            }
        }

        return $donneesFromDatabases;
    }
}