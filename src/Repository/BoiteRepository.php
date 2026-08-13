<?php

namespace App\Repository;

use App\Entity\Boite;
use App\Entity\Editor;
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

    //?Deux modes de recherche, selon la presence d'un "+" dans la saisie :
    //?- avec "+" (ex: "chat + monopoly") : mode strict, un terme doit toucher specifiquement
    //?  un article et l'autre specifiquement la boite/editeur/tags (peu importe l'ordre saisi).
    //?- sans "+" (ex: "chat monopoly") : mode large, chaque mot est cherche independamment
    //?  n'importe ou (boite OU editeur OU tags OU article), sans obligation que tous matchent.
    public function findBoitesWhereThereIsItems($search = null): array
    {
        $search = $search ?? '';
        $strictMode = str_contains($search, '+');
        $year = null;

        $rawTerms = $strictMode ? explode('+', $search) : explode(' ', $search);
        $terms = [];
        foreach ($rawTerms as $t) {
            $t = trim($t);
            if ($t === '') {
                continue;
            }
            // On ne considère comme une année qu'un nombre de 4 chiffres (ex: entre 1900 et 2099)
            if (preg_match('/^(19|20)\d{2}$/', $t)) {
                $year = $t;
            } elseif (mb_strlen($t) >= 3) {
                // Les mots de 1-2 lettres (articles: "la", "le", "un"...) matchent quasiment
                // n'importe quoi en LIKE '%...%' et noient les vrais termes de recherche.
                $terms[] = $t;
            }
        }
        $terms = array_values($terms);

        $qb = $this->createQueryBuilder('b')
            ->addSelect('e')
            ->join('b.itemsOrigine', 'i')
            ->leftJoin('b.editor', 'e')
            ->where('b.isOnline = :true')
            ->setParameter('true', true)
            ->andWhere('i.stockForSale > :min')
            ->setParameter('min', 0);

        if ($strictMode) {
            // Chaque terme doit se trouver quelque part (base commune avec le mode large)...
            foreach ($terms as $i => $term) {
                $qb->andWhere($qb->expr()->orX(
                    "LOWER(b.name) LIKE :val{$i}",
                    "LOWER(e.name) LIKE :val{$i}",
                    "LOWER(b.tags) LIKE :val{$i}",
                    "LOWER(i.name) LIKE :val{$i}"
                ))->setParameter("val{$i}", '%' . mb_strtolower($term) . '%');
            }

            // ...et en plus, au moins un terme doit toucher specifiquement un article,
            // et (un autre) au moins un doit toucher specifiquement la boite/editeur/tags.
            if (count($terms) >= 2) {
                $itemMatch = [];
                $boiteMatch = [];
                foreach ($terms as $i => $term) {
                    $itemMatch[] = "LOWER(i.name) LIKE :val{$i}";
                    $boiteMatch[] = $qb->expr()->orX(
                        "LOWER(b.name) LIKE :val{$i}",
                        "LOWER(e.name) LIKE :val{$i}",
                        "LOWER(b.tags) LIKE :val{$i}"
                    );
                }
                $qb->andWhere($qb->expr()->orX(...$itemMatch))
                   ->andWhere($qb->expr()->orX(...$boiteMatch));
            }
        } elseif (!empty($terms)) {
            // Mode large : un seul OR global (n'importe quel terme, n'importe quel champ)
            $orConditions = [];
            foreach ($terms as $i => $term) {
                $orConditions[] = "LOWER(b.name) LIKE :val{$i}";
                $orConditions[] = "LOWER(e.name) LIKE :val{$i}";
                $orConditions[] = "LOWER(b.tags) LIKE :val{$i}";
                $orConditions[] = "LOWER(i.name) LIKE :val{$i}";
                $qb->setParameter("val{$i}", '%' . mb_strtolower($term) . '%');
            }
            $qb->andWhere($qb->expr()->orX(...$orConditions));
        }

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

    /**
     * Recherche du catalogue public (formulaire SearchCatalogueType) : le texte est cherche
     * uniquement dans le(s) perimetre(s) coche(s) par le visiteur (jeu, piece, ou les 2 - le
     * perimetre coche determine QUELS CHAMPS sont regardes, pas d'obligation qu'un mot soit
     * dans le jeu ET un autre dans la piece). Chaque mot tapE doit en revanche etre trouve
     * quelque part (AND entre les mots) : sinon un mot comme "chat" tout seul peut faire
     * remonter un resultat sans rapport avec le reste de la recherche.
     *
     * @param string[] $scope 'jeu' et/ou 'piece'
     */
    public function findBoitesBySearchScope(string $search, array $scope): array
    {
        $year = null;
        $terms = [];
        foreach (explode(' ', $search) as $t) {
            $t = trim($t);
            if ($t === '') {
                continue;
            }
            // On ne considère comme une année qu'un nombre de 4 chiffres (ex: entre 1900 et 2099)
            if (preg_match('/^(19|20)\d{2}$/', $t)) {
                $year = $t;
            } elseif (mb_strlen($t) >= 3) {
                // Les mots de 1-2 lettres (articles: "la", "le", "un"...) matchent quasiment
                // n'importe quoi en LIKE '%...%' et noient les vrais termes de recherche.
                $terms[] = $t;
            }
        }
        $terms = array_values($terms);

        $qb = $this->createQueryBuilder('b')
            ->addSelect('e')
            ->join('b.itemsOrigine', 'i')
            ->leftJoin('b.editor', 'e')
            ->where('b.isOnline = :true')
            ->setParameter('true', true)
            ->andWhere('i.stockForSale > :min')
            ->setParameter('min', 0);

        //?Chaque mot doit matcher quelque part dans le perimetre coche (OR sur les champs
        //?autorises pour CE mot), et TOUS les mots doivent matcher (AND entre les mots).
        foreach ($terms as $i => $term) {
            $orConditions = [];
            if (in_array('jeu', $scope, true)) {
                $orConditions[] = "LOWER(b.name) LIKE :val{$i}";
                $orConditions[] = "LOWER(e.name) LIKE :val{$i}";
                $orConditions[] = "LOWER(b.tags) LIKE :val{$i}";
            }
            if (in_array('piece', $scope, true)) {
                $orConditions[] = "LOWER(i.name) LIKE :val{$i}";
            }
            $qb->andWhere($qb->expr()->orX(...$orConditions))
                ->setParameter("val{$i}", '%' . mb_strtolower($term) . '%');
        }

        if ($year) {
            $qb->andWhere('b.year = :year')
                ->setParameter('year', $year);
        }

        return $qb->orderBy('b.name', 'ASC')
            ->addOrderBy('b.year', 'DESC')
            ->distinct()
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

    //?Page "articles d'une boite" du catalogue : JOIN FETCH itemsOrigine +
    //?itemGroup pour eviter une requete lazy par groupe d'articles distinct
    //?quand le controleur regroupe les items par ItemGroup.
    public function findOneForArticlesPage(int $id, string $slug, Editor $editor): ?Boite
    {
        return $this->createQueryBuilder('b')
            ->addSelect('i', 'ig')
            ->leftJoin('b.itemsOrigine', 'i')
            ->leftJoin('i.itemGroup', 'ig')
            ->andWhere('b.id = :id')
            ->andWhere('b.slug = :slug')
            ->andWhere('b.editor = :editor')
            ->andWhere('b.isOnline = :true')
            ->setParameter('id', $id)
            ->setParameter('slug', $slug)
            ->setParameter('editor', $editor)
            ->setParameter('true', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
