<?php

namespace App\Repository;

use App\Entity\QuoteRequestLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteRequestLine>
 *
 * @method QuoteRequestLine|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuoteRequestLine|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuoteRequestLine[]    findAll()
 * @method QuoteRequestLine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteRequestLineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteRequestLine::class);
    }

//    /**
//     * @return QuoteRequestLine[] Returns an array of QuoteRequestLine objects
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

//    public function findOneBySomeField($value): ?QuoteRequestLine
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
