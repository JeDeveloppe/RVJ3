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
