<?php

namespace App\Repository;

use App\Entity\StorePhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StorePhoto>
 *
 * @method StorePhoto|null find($id, $lockMode = null, $lockVersion = null)
 * @method StorePhoto|null findOneBy(array $criteria, array $orderBy = null)
 * @method StorePhoto[]    findAll()
 * @method StorePhoto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StorePhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StorePhoto::class);
    }

//    /**
//     * @return StorePhoto[] Returns an array of StorePhoto objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?StorePhoto
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
