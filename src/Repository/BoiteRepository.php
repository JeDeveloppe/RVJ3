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
        $searchs = explode(" ", $search ?? '');
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
            ->addSelect('e')
            ->join('b.itemsOrigine', 'i')
            ->leftJoin('b.editor', 'e')
            ->where('b.isOnline = :true')
            ->setParameter('true', true)
            ->andWhere('i.stockForSale > :min')
            ->setParameter('min', 0);

        // Bloc de recherche textuelle (Nom boîte OR Editeur OR Nom Item), insensible a la casse
        $qb->andWhere($qb->expr()->orX(
            'LOWER(b.name) LIKE :val',
            'LOWER(e.name) LIKE :val',
            'LOWER(b.tags) LIKE :val',
            'LOWER(i.name) LIKE :val'
        ))->setParameter('val', '%' . mb_strtolower($phrase) . '%');

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

        // Créez une expression pour le "ou", insensible a la casse
        $orX = $qb->expr()->orX(
            $qb->expr()->like('LOWER(b.name)', ':val'),
            $qb->expr()->like('LOWER(e.name)', ':val')
        );

        $qb->andWhere($orX)
           ->setParameter('val', '%'.mb_strtolower($phrase).'%')
           ->join('b.editor', 'e')
           ->orderBy('b.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    //?Poids/prix moyen : le 0 peut signifier "non renseigne" (meme convention que boite.year) ou etre une
    //?vraie valeur - on calcule donc les deux versions (avec et sans les boites a 0), poids et prix traites
    //?independamment (une boite peut avoir un poids renseigne mais pas de prix, ou l'inverse).
    public function findAverageWeightAndPrice(): array
    {
        $all = $this->createQueryBuilder('b')
            ->select('AVG(b.weigth) as avgWeigth', 'AVG(b.htPrice) as avgHtPrice', 'COUNT(b.id) as nbBoites')
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $weigthExcl0 = $this->createQueryBuilder('b')
            ->select('AVG(b.weigth) as avgWeigth', 'COUNT(b.id) as nbBoites')
            ->andWhere('b.weigth > 0')
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $htPriceExcl0 = $this->createQueryBuilder('b')
            ->select('AVG(b.htPrice) as avgHtPrice', 'COUNT(b.id) as nbBoites')
            ->andWhere('b.htPrice > 0')
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return [
            'avgWeigthAll' => $all['avgWeigth'],
            'avgHtPriceAll' => $all['avgHtPrice'],
            'nbBoitesAll' => $all['nbBoites'],
            'avgWeigthExcl0' => $weigthExcl0['avgWeigth'],
            'nbBoitesWeigthExcl0' => $weigthExcl0['nbBoites'],
            'avgHtPriceExcl0' => $htPriceExcl0['avgHtPrice'],
            'nbBoitesHtPriceExcl0' => $htPriceExcl0['nbBoites'],
        ];
    }

    //?Jeux (regroupes par nom, toutes editions confondues) avec le plus d'articles vendus.
    //?
    //?Une meme piece detachee est tres souvent liee a PLUSIEURS boites (editions differentes du meme
    //?jeu - verifie : jamais entre deux jeux de noms differents, cf. session du 2026-08-09). Le lien
    //?boite<->article n'est pas un simple partitionnement propre : certaines pieces sont partagees par
    //?7 editions, d'autres par 2 seulement, sans "clusters" nets. Impossible donc d'attribuer une vente
    //?a UNE edition precise de facon fiable - on ne peut compter chaque piece qu'UNE SEULE FOIS, au
    //?niveau du nom du jeu (jamais par boite individuelle, qui gonflerait/dupliquerait le total).
    public function findGameNamesWithMostArticlesSold(int $limit = 20): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Etape 1 : chaque article compte une seule fois, associe a son nom de jeu (un article
        // n'appartient jamais qu'a un seul nom de jeu, meme s'il touche plusieurs de ses editions).
        $distinctItemsByName = $conn->fetchAllAssociative(
            'SELECT DISTINCT ib.item_id, b.name
             FROM item_boite ib
             JOIN boite b ON b.id = ib.boite_id'
        );

        $nameByItemId = [];
        foreach ($distinctItemsByName as $row) {
            $nameByItemId[(int) $row['item_id']] = $row['name'];
        }

        if (empty($nameByItemId)) {
            return [];
        }

        // Etape 2 : quantite vendue (ventes payees uniquement) pour chacun de ces articles, une seule fois.
        $quantities = $conn->fetchAllAssociative(
            'SELECT dl.item_id, SUM(dl.quantity) as qty
             FROM document_line dl
             JOIN document d ON d.id = dl.document_id
             WHERE d.bill_number IS NOT NULL AND dl.item_id IN (' . implode(',', array_keys($nameByItemId)) . ')
             GROUP BY dl.item_id'
        );

        $totalsByName = [];
        foreach ($quantities as $row) {
            $itemId = (int) $row['item_id'];
            if (!isset($nameByItemId[$itemId])) {
                continue;
            }
            $name = $nameByItemId[$itemId];
            $totalsByName[$name] = ($totalsByName[$name] ?? 0) + (int) $row['qty'];
        }

        arsort($totalsByName);

        $result = [];
        foreach (array_slice($totalsByName, 0, $limit, true) as $name => $total) {
            $result[] = ['name' => $name, 'totalQuantitySold' => $total];
        }

        return $result;
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
