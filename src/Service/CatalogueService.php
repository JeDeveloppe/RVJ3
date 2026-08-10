<?php

namespace App\Service;

use App\Repository\ItemRepository;

class CatalogueService
{
    public function __construct(
        private ItemRepository $itemRepository
    )
    {
    }

    public function addNumberOfItemWithStockNotNull(array $donneesFromDatabases):array{
        //calcul par boite du nombre d'articles en stock, en une seule requete
        //groupee (evite une requete par boite via boite.getItemsOrigine())
        $boiteIds = array_map(fn($boite) => $boite->getId(), $donneesFromDatabases);
        $counts = $this->itemRepository->countItemsWithStockForSaleByBoiteIds($boiteIds);

        foreach($donneesFromDatabases as $boite){
            $boite->setNumberOfItemWithStockNotNull($counts[$boite->getId()] ?? 0);
        }

        return $donneesFromDatabases;
    }
}