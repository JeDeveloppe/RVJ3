<?php

namespace App\Repository;

use App\Entity\OpeningHours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OpeningHours>
 *
 * @method OpeningHours|null find($id, $lockMode = null, $lockVersion = null)
 * @method OpeningHours|null findOneBy(array $criteria, array $orderBy = null)
 * @method OpeningHours[]    findAll()
 * @method OpeningHours[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OpeningHoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpeningHours::class);
    }

//    /**
//     * @return OpeningHours[] Returns an array of OpeningHours objects
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

//    public function findOneBySomeField($value): ?OpeningHours
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
