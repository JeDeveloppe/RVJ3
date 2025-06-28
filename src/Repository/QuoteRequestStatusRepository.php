<?php

namespace App\Repository;

use App\Entity\QuoteRequestStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteRequestStatus>
 *
 * @method QuoteRequestStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuoteRequestStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuoteRequestStatus[]    findAll()
 * @method QuoteRequestStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteRequestStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteRequestStatus::class);
    }

//    /**
//     * @return QuoteRequestStatus[] Returns an array of QuoteRequestStatus objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('q.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?QuoteRequestStatus
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
