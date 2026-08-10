<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Item>
 *
 * @method Item|null find($id, $lockMode = null, $lockVersion = null)
 * @method Item|null findOneBy(array $criteria, array $orderBy = null)
 * @method Item[]    findAll()
 * @method Item[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function findByStockForSaleIsNull(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.stockForSale = :val')
            ->setParameter('val', 0)
            ->orderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllItemsWithStockForSaleNotNull(): array
    {

        $items =  $this->createQueryBuilder('i')
            ->andWhere('i.stockForSale > :val')
            ->setParameter('val', 0)
            ->getQuery()
            ->getResult()
        ;

        return $items;
    }

    public function findAllItemsWithStockForSaleNotNullOrderByUpdatedAtDesc(): array
    {

        $items =  $this->createQueryBuilder('i')
            ->andWhere('i.stockForSale > :val')
            ->setParameter('val', 0)
            ->orderBy('i.updatedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return $items;
    }

    //?Nombre d'items en stock par boite, en une seule requete groupee (evite une
    //?requete par boite via boite.getItemsOrigine(), cf. CatalogueService).
    //?Retourne un tableau [boiteId => nombre].
    public function countItemsWithStockForSaleByBoiteIds(array $boiteIds): array
    {
        if (empty($boiteIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('i')
            ->select('bo.id AS boiteId', 'COUNT(i.id) AS nb')
            ->join('i.BoiteOrigine', 'bo')
            ->andWhere('bo.id IN (:boiteIds)')
            ->andWhere('i.stockForSale > 0')
            ->setParameter('boiteIds', $boiteIds)
            ->groupBy('bo.id')
            ->getQuery()
            ->getArrayResult()
        ;

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['boiteId']] = (int) $row['nb'];
        }

        return $counts;
    }

    //?Memes criteres que findAllItemsWithStockForSaleNotNull, mais avec boiteOrigine
    //?JOIN FETCH : evite un aller-retour BDD par item quand on doit parcourir
    //?item.getBoiteOrigine() pour chaque resultat (catalogue pieces detachees).
    public function findAllItemsWithStockForSaleNotNullAndBoiteOrigine(): array
    {
        return $this->createQueryBuilder('i')
            ->addSelect('bo')
            ->leftJoin('i.BoiteOrigine', 'bo')
            ->andWhere('i.stockForSale > :val')
            ->setParameter('val', 0)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllItemsWithStockForSaleNotNullOrderByUpdatedAtDescAndBoiteOrigine(): array
    {
        return $this->createQueryBuilder('i')
            ->addSelect('bo')
            ->leftJoin('i.BoiteOrigine', 'bo')
            ->andWhere('i.stockForSale > :val')
            ->setParameter('val', 0)
            ->orderBy('i.updatedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    //?Articles les plus vendus. Seules les ventes reellement payees comptent : document.billNumber IS NOT
    //?NULL (regle deja utilisee ailleurs dans l'admin pour le calcul du CA, cf.
    //?DocumentRepository::countSumOfAllDocumentsWhenDocumentIsPayed) - exclut devis/non payes.
    public function findBestSellingItems(int $limit = 50): array
    {
        return $this->createQueryBuilder('i')
            ->select('i.id', 'i.name', 'i.reference', 'SUM(dl.quantity) as totalQuantitySold')
            ->join('i.documentLines', 'dl')
            ->join('dl.document', 'd')
            ->andWhere('d.billNumber IS NOT NULL')
            ->groupBy('i.id')
            ->orderBy('totalQuantitySold', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    //?JOIN FETCH document (pour la date/numero) en une seule requete, meme principe que
    //?StockRepository::findWithOccasionsAndBoites() - evite de charger chaque document a la
    //?demande (N+1) quand on affiche l'historique des ventes d'un article.
    public function findWithDocumentLines(int $id): ?Item
    {
        $item = $this->createQueryBuilder('i')
            ->addSelect('dl', 'd')
            ->leftJoin('i.documentLines', 'dl')
            ->leftJoin('dl.document', 'd')
            ->andWhere('i.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $item;
    }

//    /**
//     * @return Item[] Returns an array of Item objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Item
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
