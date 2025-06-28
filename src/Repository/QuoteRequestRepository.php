<?php

namespace App\Repository;

use App\Entity\QuoteRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteRequest>
 *
 * @method QuoteRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuoteRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuoteRequest[]    findAll()
 * @method QuoteRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteRequest::class);
    }

    public function countQuoteRequestWhoMustByTraited(): int
    {
        return $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.isSendByEmail = :false')
            ->setParameter('false', FALSE)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findUniqueQuoteRequestWhereStatusIsBeforeSubmission(User $user)
    {
        return $this->createQueryBuilder('q')
            ->where('q.user = :user')
            ->setParameter('user', $user)
            ->join('q.quoteRequestStatus', 'qrs')
            ->andWhere('qrs.level < :level')
            ->setParameter('level', 2)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return QuoteRequest[] Returns an array of QuoteRequest objects
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

//    public function findOneBySomeField($value): ?QuoteRequest
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
