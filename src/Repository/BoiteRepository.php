<?php

namespace App\Repository;

use App\Entity\Boite;
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

    // public function findBoitesWhereThereIsItems($search = null): array
    // {
    //     $searchs = explode(" ", $search);
    //     $words = [];
    //     $year = "";

    //     foreach($searchs as $search){
    //         if(is_numeric($search)){
    //             $year = $search;
    //         }else{
    //             $words[] = $search;
    //         }
    //     }
    //     $str = implode(' ', $words);
    //     $phrase = str_replace(" ","%",$str);

    //     $results =  $this->createQueryBuilder('b')
    //         ->where('b.isOnline = :true')
    //         ->setParameter('true', true)
    //         ->andWhere('b.name LIKE :val')
    //         ->setParameter('val', '%'.$phrase.'%')
    //         ->join('b.itemsOrigine', 'i')
    //         ->join('b.editor', 'e')
    //         ->orWhere('e.name LIKE :val')
    //         ->andWhere('b.year LIKE :year')
    //         ->andWhere('i.stockForSale > :minimum')
    //         ->orWhere('i.name LIKE :val')
    //         ->setParameter('val', '%'.$phrase.'%')
    //         ->setParameter('minimum', 0)
    //         ->setParameter('year', '%'.$year.'%')
    //         ->orderBy('b.id', 'DESC')
    //         ->getQuery()
    //         ->getResult()
    //     ;

    //     $donnees = [];

    //     foreach($results as $donneesFromDatabase){
    //         if(count($donneesFromDatabase->getItemsOrigine()) > 0 OR count($donneesFromDatabase->getItemsSecondaire()) > 0){

    //             array_push($donnees,$donneesFromDatabase);

    //         }
    //     }

    //     return $donnees;
    // }

    public function findBoitesWhereThereIsItems($search = null): array
    {
        $searchs = explode(" ", $search);
        $words = [];
        $year = null;

        foreach ($searchs as $s) {
            // On ne considère comme une année qu'un nombre de 4 chiffres
            // (ex: entre 1900 et 2099)
            if (is_numeric($s) && preg_match('/^(19|20)\d{2}$/', $s)) {
                $year = $s;
            } else {
                $words[] = $s;
            }
        }

        // Si on a extrait un nombre qui n'était pas une année (ex: "1000"), 
        // on le remet dans les mots de recherche
        $words = array_filter(array_map('trim', $words));
        $phrase = implode('%', $words);
        
        $qb = $this->createQueryBuilder('b')
            ->join('b.itemsOrigine', 'i')
            ->leftJoin('b.editor', 'e')
            ->where('b.isOnline = :true')
            ->setParameter('true', true)
            ->andWhere('i.stockForSale > :min')
            ->setParameter('min', 0);

        // Bloc de recherche textuelle (Nom boîte OR Editeur OR Nom Item)
        $qb->andWhere($qb->expr()->orX(
            'b.name LIKE :val',
            'e.name LIKE :val',
            'b.tags LIKE :val',
            'i.name LIKE :val'
        ))->setParameter('val', '%' . $phrase . '%');

        // Si une année a été détectée, on l'ajoute comme condition supplémentaire
        if ($year) {
            $qb->andWhere('b.year = :year')
            ->setParameter('year', $year);
        }

        return $qb->orderBy('b.id', 'DESC')
                ->distinct() // Évite les doublons
                ->getQuery()
                ->getResult();
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

        // $results =  $this->createQueryBuilder('b')
        //     // ->where('b.isOnline = :true')
        //     // ->setParameter('true', true)
        //     ->where('b.isForAdherenteStructure = :true')
        //     ->setParameter('true', true)
        //     ->andWhere('b.name LIKE :val')
        //     ->setParameter('val', '%'.$phrase.'%')
        //     ->join('b.editor', 'e')
        //     ->orWhere('e.name LIKE :val')
        //     ->orderBy('b.id', 'DESC')
        //     ->getQuery()
        //     ->getResult()
        // ;

        // return $results;

        $qb = $this->createQueryBuilder('b');

        $qb->where('b.isForAdherenteStructure = :true')
           ->setParameter('true', true);

        // Créez une expression pour le "ou"
        $orX = $qb->expr()->orX(
            $qb->expr()->like('b.name', ':val'),
            $qb->expr()->like('e.name', ':val')
        );

        $qb->andWhere($orX)
           ->setParameter('val', '%'.$phrase.'%')
           ->join('b.editor', 'e')
           ->orderBy('b.id', 'DESC');

        return $qb->getQuery()->getResult();
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
