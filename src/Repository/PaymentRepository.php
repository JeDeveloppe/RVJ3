<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\DBAL\Types\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 *
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findPaiementsAndReturnCA($month,$year)
    {
        return $this->createQueryBuilder('p')
            ->join('p.document','d')
            ->select('SUM(d.totalExcludingTax) as totalExcludingTaxInMonth')
            ->where('MONTH(p.timeOfTransaction) = :month')
            ->setParameter('month', $month)
            ->andWhere('YEAR(p.timeOfTransaction) = :year')
            ->setParameter('year', $year)
            ->getQuery()->getSingleScalarResult();
    }

    public function findNumberOfPaiements(int $month,int $year)
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('MONTH(p.timeOfTransaction) = :month')
            ->setParameter('month', $month)
            ->andWhere('YEAR(p.timeOfTransaction) = :year')
            ->setParameter('year', $year)
            ->getQuery()->getSingleScalarResult();
    }

    public function findPaiements(int $month,int $year): array
    {
        return $this->createQueryBuilder('p')
            ->where('MONTH(p.timeOfTransaction) = :month')
            ->setParameter('month', $month)
            ->andWhere('YEAR(p.timeOfTransaction) = :year')
            ->setParameter('year', $year)
            ->getQuery()->getResult();
    }


//    /**
//     * @return Payment[] Returns an array of Payment objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Payment
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    //?Un paiement HelloAsso est deja rattache a une commande si son numero figure dans le detail (rapprochement)
    //?ou si une commande a exactement la meme heure de transaction (enregistrement normal, qui ne garde pas le numero).
    public function isHelloAssoPaymentAlreadyRecorded(string $helloAssoPaymentId, \DateTimeImmutable $paidAt): bool
    {
        return null !== $this->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.timeOfTransaction = :paidAt OR p.details LIKE :details')
            ->setParameter('paidAt', $paidAt, Types::DATETIME_IMMUTABLE)
            ->setParameter('details', '%HelloAsso n°'.$helloAssoPaymentId.'%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
