<?php

namespace App\Repository;

use App\Entity\Document;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 *
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function findDocumentsToBeTraitedDailyWithStatus($status): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.documentStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('d.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLastEntryFromThisYear($column, $year)
    {
        $query =  $this->createQueryBuilder('d')
            ->where('YEAR(d.createdAt) = :year')
            ->andWhere('d.'.$column.' IS NOT NULL')
            ->setParameter('year', $year)
            ->orderBy('d.'.$column, 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
        ;

        return $query;
    }

    public function findDocumentsToDeleteWhenEndOfQuoteValidationIsToOld($now){

        return $this->createQueryBuilder('d')
            ->where('d.endOfQuoteValidation < :now')
            ->andWhere('d.billNumber IS NULL') //pas de facture
            ->andWhere('d.isQuoteReminder = :true') //devis bien relancer par email, on a donc remis X jours
            ->andWhere('d.isLastQuote = :false') //n'est pas le dernier document de la base
            ->setParameter('now', $now)
            ->setParameter('true', true)
            ->setParameter('false', false)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDocumentsToDeleteWhenIsDeleteByUserAndIsNotTheLastInDatabase(){

        return $this->createQueryBuilder('d')
            ->where('d.isDeleteByUser = :true')
            ->andWhere('d.billNumber IS NULL') //pas de facture
            ->andWhere('d.isLastQuote = :false') //n'est pas le dernier document de la base
            ->setParameter('true', true)
            ->setParameter('false', false)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByDevisToReminder($now){

        return $this->createQueryBuilder('d')
            ->where('d.endOfQuoteValidation < :now')
            ->andWhere('d.billNumber IS NULL')
            ->andWhere('d.isQuoteReminder = :false')
            ->andWhere('d.isDeleteByUser = :false')
            ->setParameter('now', $now)
            ->setParameter('false', false)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByDocumentWithPaiementInYear(string $billTag, int $year = null){

        if(is_int($year)){
            $year = substr($year, -2);
        }

        return $this->createQueryBuilder('d')
            ->where('d.billNumber LIKE :docstart')
            ->setParameter('docstart', $billTag.$year.'%') //only 23 in 2023
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDocumentsCreatedAfterDateAndNotBilled(DateTimeImmutable $date){

        return $this->createQueryBuilder('d')
            ->where('d.createdAt > :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countSumOfAllDocumentsWhenDocumentIsPayed(){

        return $this->createQueryBuilder('d')
            ->select('SUM(d.totalExcludingTax)')
            ->where('d.billNumber IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
    

//    /**
//     * @return Document[] Returns an array of Document objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Document
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
