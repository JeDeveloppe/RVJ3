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

    /**
     * Regroupe les documents par ville de livraison, avec leur nombre - pour la carte des
     * ventes (admin/stats). Un marqueur par ville (>1000 distinctes) : illisible tel quel,
     * mais le JS applique un clustering (regroupement visuel qui se deplie au zoom).
     *
     * @return array<int, array{name: string, latitude: string, longitude: string, total: int}>
     */
    public function countDocumentsGroupedByDeliveryCity(): array
    {
        return $this->createQueryBuilder('d')
            ->select('c.name as name, c.latitude as latitude, c.longitude as longitude, COUNT(d.id) as total')
            ->join('d.deliveryCity', 'c')
            ->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult()
        ;
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

    public function countDocumentsToBeTraitedDailyByStatuses(array $statuses): int
    {
        if (empty($statuses)) {
            return 0;
        }

        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.documentStatus IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    //?Definition UNIQUE de "en attente de paiement" : billNumber pas encore genere (pas
    //?facture) ET pas abandonne par le client. Reprise par countWaitingToBePaid() et
    //?findDocumentsWaitingToBePaid() - evite que ce critere diverge d'un endroit a l'autre
    //?(cf. bug corrige ou le dashboard utilisait par erreur isLastQuote a la place).
    private function queryWaitingToBePaid()
    {
        return $this->createQueryBuilder('d')
            ->where('d.billNumber IS NULL')
            ->andWhere('d.isDeleteByUser = :false')
            ->setParameter('false', false)
        ;
    }

    public function countWaitingToBePaid(): int
    {
        return (int) $this->queryWaitingToBePaid()
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findDocumentsWaitingToBePaid(): array
    {
        //?JOIN FETCH : les 2 usages de cette methode (dashboard + page dediee) parcourent
        //?ensuite documentLines pour chaque document - sans ca, Doctrine charge les lignes
        //?une par une au fil de la boucle (1 requete par document, N+1).
        return $this->queryWaitingToBePaid()
            ->leftJoin('d.documentLines', 'dl')
            ->addSelect('dl')
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

    public function findByDocumentWithPaiementInYear(string $billTag, ?int $year = null){

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
            ->andWhere('d.billNumber IS NULL') //pas de facture
            ->andWhere('d.isDeleteByUser = :false')
            ->setParameter('date', $date)
            ->setParameter('false', false)
            ->getQuery()
            ->getResult()
        ;
    }

    //utilisé pour la réconciliation automatique HelloAsso à la connexion admin, avant la suppression des devis expirés
    public function findDocumentsNotBilled(){

        return $this->createQueryBuilder('d')
            ->where('d.billNumber IS NULL') //pas de facture
            ->andWhere('d.isDeleteByUser = :false')
            ->setParameter('false', false)
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
