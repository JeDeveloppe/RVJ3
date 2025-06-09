<?php

namespace App\Repository;

use App\Entity\Panier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panier>
 *
 * @method Panier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Panier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Panier[]    findAll()
 * @method Panier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }

    public function findOccasionsByUser($user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.occasion IS NOT NULL')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findBoitesByUser($user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.boite IS NOT NULL')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findItemsByUser($user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.item IS NOT NULL')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllOccasionsInCart(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.occasion IS NOT NULL')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOccasionsInCart($id): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.occasion :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findPaniersToDeleteWhenEndOfValidationIsToOld($now){

        return $this->createQueryBuilder('p')
            ->where('p.createdAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countActiveCarts(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)') // Sélectionnez uniquement le COUNT de l'ID pour optimiser
            ->getQuery()
            ->getSingleScalarResult(); // Récupère le résultat sous forme de valeur scalaire (un entier)
    }

//    /**
//     * @return Panier[] Returns an array of Panier objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Panier
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
