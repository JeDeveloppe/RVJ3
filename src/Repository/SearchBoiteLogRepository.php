<?php

namespace App\Repository;

use App\Entity\SearchBoiteLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SearchBoiteLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchBoiteLog::class);
    }

    //?Double-clic sur le bouton de recherche, retour arriere du navigateur, resoumission...
    //?meme requete GET rejouee plusieurs fois en quelques secondes par le meme visiteur :
    //?evite de gonfler artificiellement le compteur d'occurrences du "nuage de recherches"
    //?avec ce qui n'est pas un vrai signal de demande supplementaire.
    public function hasRecentIdenticalLog(string $query, \DateTimeImmutable $since): bool
    {
        return null !== $this->createQueryBuilder('s')
            ->select('s.id')
            ->andWhere('s.query = :query')
            ->andWhere('s.createdAt >= :since')
            ->setParameter('query', $query)
            ->setParameter('since', $since)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteOldLogs(int $limit = 500): void
    {
        // On cherche l'ID du 500ème log le plus récent
        $lastIdResult = $this->createQueryBuilder('s')
            ->select('s.id')
            ->orderBy('s.id', 'DESC')
            ->setFirstResult($limit - 1)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastIdResult) {
            $lastId = $lastIdResult['id'];
            
            // On supprime tout ce qui est strictement plus ancien que cet ID
            $this->getEntityManager()->createQuery(
                'DELETE FROM App\Entity\SearchBoiteLog s WHERE s.id < :lastId'
            )
            ->setParameter('lastId', $lastId)
            ->execute();
        }
    }

    /**
     * Regroupe les recherches sans resultat par terme, avec leur frequence - pour reperer
     * les jeux que les visiteurs cherchent sans les trouver en ligne.
     *
     * @return array<int, array{query: string, occurrences: int, lastSearchedAt: \DateTimeInterface}>
     */
    public function findGroupedFailedSearches(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.query as query, COUNT(s.id) as occurrences, MAX(s.createdAt) as lastSearchedAt')
            //?Filtre explicite (ne pas se fier uniquement au fait qu'on ne log plus que les
            //?echecs a l'ecriture) : des lignes plus anciennes, enregistrees avant ce
            //?changement, peuvent avoir un resultsCount > 0 et fausser le regroupement.
            ->andWhere('s.resultsCount = 0')
            ->groupBy('s.query')
            ->orderBy('occurrences', 'DESC')
            ->addOrderBy('lastSearchedAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }
}