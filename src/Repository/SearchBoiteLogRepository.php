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
}