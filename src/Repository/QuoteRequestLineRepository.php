<?php

namespace App\Repository;

use App\Entity\QuoteRequest;
use App\Entity\QuoteRequestLine;
use App\Entity\User;
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

    public function countQuoteRequestLines(User $user): int
    {
        return $this->createQueryBuilder('qrl')
            ->select('COUNT(qrl.id)') 
            ->join('qrl.quoteRequest', 'qr')// Sélectionnez uniquement le COUNT de l'ID pour optimiser
            ->where('qr.user = :user')
            ->setParameter('user', $user)
            ->join('qr.quoteRequestStatus', 'qrs')
            ->andWhere('qrs.level = :level')
            ->setParameter('level', 1)
            ->getQuery()
            ->getSingleScalarResult(); // Récupère le résultat sous forme de valeur scalaire (un entier)
    }

    public function findQuoteRequestLineToDelete(User $user, $quoteRequestId, int $id): ?QuoteRequestLine
    {
        return $this->createQueryBuilder('qrl')
            ->where('qrl.id = :id')
            ->setParameter('id', $id)
            ->join('qrl.quoteRequest', 'qr')
            ->andWhere('qr.id = :quoteRequestId')
            ->setParameter('quoteRequestId', $quoteRequestId)
            ->join('qr.user', 'u')
            ->andWhere('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
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
