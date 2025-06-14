<?php

namespace App\Repository;

use App\Entity\Delivery;
use App\Entity\ShippingMethod;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Delivery>
 *
 * @method Delivery|null find($id, $lockMode = null, $lockVersion = null)
 * @method Delivery|null findOneBy(array $criteria, array $orderBy = null)
 * @method Delivery[]    findAll()
 * @method Delivery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Delivery::class);
    }

    
    public function findCostByDeliveryShippingMethod(ShippingMethod $shippingMethod,int $weigthPanier, User $user)
    {
        return $this->createQueryBuilder('d')
            ->where('d.shippingMethod = :method')
            ->andWhere('d.start <= :weigth')
            ->andWhere('d.end > :weigth')
            ->andWhere('d.country = :country')
            ->setParameter('method', $shippingMethod)
            ->setParameter('weigth', $weigthPanier)
            ->setParameter('country', $user->getCountry())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            // ->getSingleResult()
        ;
    }


//    /**
//     * @return Delivery[] Returns an array of Delivery objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Delivery
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
