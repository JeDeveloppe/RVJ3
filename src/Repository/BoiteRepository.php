<?php

namespace App\Repository;

use App\Entity\Boite;
use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Boite>
 *
 * @method Boite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Boite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Boite[]    findAll()
 * @method Boite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BoiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boite::class);
    }

    public function findDistinctEditors(): array
    {
        return $this->createQueryBuilder('b')
            ->select('b.initeditor')
            ->groupBy('b.initeditor')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findBoitesWhereThereIsItems($search = null): array
    {
        $searchs = explode(" ", $search);
        $words = [];
        $year = "";

        foreach($searchs as $search){
            if(is_numeric($search)){
                $year = $search;
            }else{
                $words[] = $search;
            }
        }
        $str = implode(' ', $words);
        $phrase = str_replace(" ","%",$str);

        $results =  $this->createQueryBuilder('b')
            ->where('b.isOnline = :true')
            ->setParameter('true', true)
            ->andWhere('b.name LIKE :val')
            ->setParameter('val', '%'.$phrase.'%')
            ->join('b.itemsOrigine', 'i')
            ->join('b.editor', 'e')
            ->orWhere('e.name LIKE :val')
            ->andWhere('b.year LIKE :year')
            ->andWhere('i.stockForSale > :minimum')
            ->orWhere('i.name LIKE :val')
            ->setParameter('val', '%'.$phrase.'%')
            ->setParameter('minimum', 0)
            ->setParameter('year', '%'.$year.'%')
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        $donnees = [];

        foreach($results as $donneesFromDatabase){
            if(count($donneesFromDatabase->getItemsOrigine()) > 0 OR count($donneesFromDatabase->getItemsSecondaire()) > 0){

                array_push($donnees,$donneesFromDatabase);

            }
        }

        return $donnees;
    }

    public function findBoitesForMemberStructure($search = null): array
    {
        $searchs = explode(" ", $search);
        $words = [];
        $year = "";

        foreach($searchs as $search){
            if(is_numeric($search)){
                $year = $search;
            }else{
                $words[] = $search;
            }
        }
        $str = implode(' ', $words);
        $phrase = str_replace(" ","%",$str);

        $results =  $this->createQueryBuilder('b')
            // ->where('b.isOnline = :true')
            // ->setParameter('true', true)
            ->where('b.name LIKE :val')
            ->setParameter('val', '%'.$phrase.'%')
            ->join('b.editor', 'e')
            ->orWhere('e.name LIKE :val')
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return $results;
    }

//    /**
//     * @return Boite[] Returns an array of Boite objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Boite
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
