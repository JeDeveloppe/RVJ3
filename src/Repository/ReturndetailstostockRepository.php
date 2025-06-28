<?php

namespace App\Repository;

use App\Entity\Returndetailstostock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Returndetailstostock>
 *
 * @method Returndetailstostock|null find($id, $lockMode = null, $lockVersion = null)
 * @method Returndetailstostock|null findOneBy(array $criteria, array $orderBy = null)
 * @method Returndetailstostock[]    findAll()
 * @method Returndetailstostock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReturndetailstostockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Returndetailstostock::class);
    }

    public function countReturnStockWhereMustBeTraited(): int
    {
        return $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

//    /**
//     * @return Returndetailstostock[] Returns an array of Returndetailstostock objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Returndetailstostock
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
