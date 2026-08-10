<?php

namespace App\Repository;

use App\Entity\Occasion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Occasion>
 *
 * @method Occasion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Occasion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Occasion[]    findAll()
 * @method Occasion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OccasionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Occasion::class);
    }

    public function searchOccasionsInCatalogue(
        array $ages,
        array $players,
        array $search_durations,
        array $choices): array
    {

        //?si je ne rechereche pas de joueurs, je cherche tous les joueurs de la categorie
        $players = $this->returnPlayers($players, $choices);

        //?si je n'ai pas de durées, je cherche toutes les durées de la categorie
        $durations = $this->returnDurations($search_durations, $choices);

        //si je n'ai pas d'ages, je cherche tous les ages de la categorie
        $ages = $this->returnAges($ages, $choices);

        $queryWithPlayersIn =  $this->createQueryBuilder('o')
            ->join('o.boite','b')
            ->join('b.editor','e')
            ->join('b.durationGame','d')
            ->join('o.stock','s')
            ->where('s.name = :stock_name')
            ->andWhere('b.playersMin IN (:players)')
            ->orWhere('b.playersMax IN (:players)')
            ->andWhere('d.name IN (:durations)')
            ->andWhere('b.age IN (:ages)')
            ->andWhere('o.isOnline = :online')
            ->setParameters([
                'players' => $players,
                'durations' => $durations,
                'ages' => $ages,
                'online' =>  true,
                'stock_name' => 'SUR LE SITE' //DEFAULT
            ])
            ->orderBy('o.createdAt', 'DESC')
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return $queryWithPlayersIn;
    }

    public function findOccasionsInCatalogueWherePlayersBetweenMinAndMax($players_choices, $durations_choices, $ages_choices, $choices)
    {
        $players = $this->returnPlayers($players_choices, $choices);
        $durations = $this->returnDurations($durations_choices, $choices);
        $ages = $this->returnAges($ages_choices, $choices);
        $querys = [];

        for($i = 0; $i < count($players); $i++){
            
            $query[$i] =  $this->createQueryBuilder('o')
                    ->join('o.boite','b')
                    ->join('b.editor','e')
                    ->join('b.durationGame','d')
                    ->join('o.stock','s')
                    ->where('s.name = :stock_name')
                    ->andWhere('b.playersMin <= :player')
                    ->andWhere('b.playersMax >= :player')
                    ->andWhere('d.name IN (:durations)')
                    ->andWhere('b.age IN (:ages)')
                    ->andWhere('o.isOnline = :online')
                    ->setParameters([
                        'player' => $players[$i],
                        'durations' => $durations,
                        'ages' => $ages,
                        'online' =>  true,
                        'stock_name' => 'SUR LE SITE' //DEFAULT
                    ])
                    ->orderBy('o.id', 'DESC')
                    ->getQuery()
                    ->getResult()
                ;

            $querys = array_unique(array_merge($querys, $query[$i]), SORT_REGULAR);
        }
        return $querys;

    }

    public function findAleatoireOccasionsByAgeWhitoutThisOccasion(int $age, Occasion $occasion): array
    {

        return $this->createQueryBuilder('o')
            ->join('o.boite','b')
            ->join('o.stock','s')
            ->where('o.isOnline = :online')
            ->andWhere('o.isReserved = :false')
            ->andWhere('s.name = :stock_name')
            ->andWhere('b.age = :age')
            ->andWhere('o.id != :occasionId')
            ->setParameters([
                'occasionId' => $occasion->getId(),
                'age' => $age,
                'online' =>  true,
                'false' => false,
                'stock_name' => 'SUR LE SITE' //DEFAULT
            ])
            ->orderBy('o.id', 'ASC')
            ->getQuery()
            ->setMaxResults(20)
            ->getResult()
        ;
    }

    public function findUniqueOccasionWhenReferenceAndSlugAreOk(string $occasionReference, string $editor_slug, string $boiteSlug)
    {
        $query = $this->createQueryBuilder('o')
        ->join('o.boite','b')
        ->join('b.editor', 'e')
        ->where('b.slug = :slug')
        ->andWhere('o.reference = :referenceV3')
        ->andWhere('e.slug = :editorSlug')
        ->setParameters([
            'slug' => $boiteSlug,
            'editorSlug' => $editor_slug,
            'referenceV3' => $occasionReference,
        ])
        ->getQuery()
        ->getResult()
        ;

        return $query;

    }

    public function findUniqueOccasionByRefrenceV2AndSlugsAreOk(string $occasionReference, string $editor_slug, string $boiteSlug)
    {
        $references = explode('-', $occasionReference);

        return $this->createQueryBuilder('o')
        ->join('o.boite','b')
        ->join('b.editor', 'e')
        ->where('b.slug = :slug')
        ->andWhere('o.reference = :referenceV2')
        ->andWhere('e.slug = :editorSlug')
        ->setParameters([
            'slug' => $boiteSlug,
            'editorSlug' => $editor_slug,
            'referenceV2' => $references[1].'-'.$references[0],
        ])
        ->getQuery()
        ->getResult()
        // ->getSingleResult()
        ;

    }

    public function findOccasionsInCatalogueByNameOrEditor($searchName)
    {

        return  $this->createQueryBuilder('o')
            ->join('o.boite','b')
            ->join('b.editor','e')
            ->join('b.durationGame','d')
            ->join('o.stock','s')
            ->where('b.name LIKE :searchName')
            ->andWhere('s.name = :stock_name')
            ->orWhere('e.name LIKE :searchName')
            ->andWhere('o.isOnline = :online')
            ->setParameters([
                'searchName' => '%'.$searchName.'%',
                'online' =>  true,
                'stock_name' => 'SUR LE SITE' //DEFAULT
            ])
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOccasionsInCatalogueWherePlayerMinAndPlayerMaxAreSame($players, $durations, $ages, $choices)
    {
        $players = $this->returnPlayers($players, $choices);
        $durations = $this->returnDurations($durations, $choices);
        $ages = $this->returnAges($ages, $choices);

        $query =  $this->createQueryBuilder('o')
            ->join('o.boite','b')
            ->join('b.editor','e')
            ->join('b.durationGame','d')
            ->join('o.stock','s')
            ->where('s.name = :stock_name')
            ->andWhere('b.playersMin IN (:players)')
            ->andWhere('b.playersMax IN (:players)')
            ->andWhere('d.name IN (:durations)')
            ->andWhere('b.age IN (:ages)')
            ->andWhere('o.isOnline = :online')
            ->setParameters([
                'players' => $players,
                'durations' => $durations,
                'ages' => $ages,
                'online' =>  true,
                'stock_name' => 'SUR LE SITE' //DEFAULT
            ])
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return $query;
    }


    private function returnDurations(array $search_durations, $choices){

        $durations = $search_durations;

        //?si je n'eai pas de durées, je cherche toutes les durées de la categorie
        if(count($search_durations) == 0){
            foreach($choices['durations_options_for_form'] as $choice){
                $durations[] = $choice;
            }
        }

        return $durations;
    }

    private function returnAges(array $search_ages, $choices){

        $ages = $search_ages;

        if(count($ages) == 0){
            foreach($choices['ages_options_for_form'] as $choice){
                $ages[] = $choice;
            }
        }

        //?for search in repository 12 and after...
        if(in_array(12, $ages)){
            for($i = 13; $i <= 99; $i++){
                $ages[] = $i;
            }
        }

        return $ages;
    }

    private function returnPlayers(array $search_players, $choices){

        $players = $search_players;

        if(count($players) == 0){
            $players = [];
            foreach($choices['players_options_for_form'] as $choice){
                $players[] = $choice;
            }
        }

        if(count($players) == 1 && $players[0] == 6){
            $players = [];
            foreach($choices['players_in_database'] as $choice){
                if($choice->getName() > 5){
                    $players[] = $choice;
                }
            }
        }

        return $players;
    }

    public function returnAllOccasionsOfflineAndBilledIsNullAndReservedIsNull(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.isOnline = :false')
            ->andWhere('o.isBilled IS NULL')   
            ->andWhere('o.isReserved IS NULL')
            ->setParameter('false', false)
            ->orderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    //?JOIN FETCH document (pour la date/numero) en une seule requete, meme principe que
    //?StockRepository::findWithOccasionsAndBoites() / ItemRepository::findWithDocumentLines().
    public function findWithDocumentLines(int $id): ?Occasion
    {
        return $this->createQueryBuilder('o')
            ->addSelect('dl', 'd')
            ->leftJoin('o.documentLines', 'dl')
            ->leftJoin('dl.document', 'd')
            ->andWhere('o.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

//    /**
//     * @return Occasion[] Returns an array of Occasion objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Occasion
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
