<?php

namespace App\Repository;

use App\Entity\OffSiteOccasionSale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OffSiteOccasionSale>
 *
 * @method OffSiteOccasionSale|null find($id, $lockMode = null, $lockVersion = null)
 * @method OffSiteOccasionSale|null findOneBy(array $criteria, array $orderBy = null)
 * @method OffSiteOccasionSale[]    findAll()
 * @method OffSiteOccasionSale[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OffSiteOccasionSaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OffSiteOccasionSale::class);
    }

     public function countSumOfAllOccasionSales(): int
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.movementPrice)')
            ->getQuery()
            ->getSingleScalarResult();
    }

//    /**
//     * @return OffSiteOccasionSale[] Returns an array of OffSiteOccasionSale objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?OffSiteOccasionSale
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
