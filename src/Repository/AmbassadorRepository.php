<?php

namespace App\Repository;

use App\Entity\Ambassador;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ambassador>
 *
 * @method Ambassador|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ambassador|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ambassador[]    findAll()
 * @method Ambassador[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AmbassadorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ambassador::class);
    }

    public function findAmbassadorsForCarte(): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('c', 'd')
            ->join('a.city', 'c')
            ->join('c.department', 'd')
            ->where('a.onTheCarte = :true')
            ->setParameter('true', true)
            ->orderBy('c.department', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
//    /**
//     * @return Ambassador[] Returns an array of Ambassador objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ambassador
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
