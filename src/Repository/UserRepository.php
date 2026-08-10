<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findInscriptions($month,$year)
    {
        return $this->createQueryBuilder('p')
            ->where('MONTH(p.createdAt) = :month')
            ->setParameter('month', $month)
            ->andWhere('YEAR(p.createdAt) = :year')
            ->setParameter('year', $year)
            ->getQuery()->getResult();
    }

    //return number of users
    public function countUsers(){
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    //?Clients avec le plus de commandes payees. Seules les commandes reellement payees comptent :
    //?document.payment.timeOfTransaction IS NOT NULL (meme regle que partout ailleurs dans
    //?l'admin pour distinguer une commande payee d'un devis/non paye). Exclut le compte
    //?generique "client de passage" (ventes en boutique sans compte client, cf.
    //?DocumentService/OffSiteOccasionSaleService/UserService) - affiche a part, cf.
    //?findClientDePassageOrderCount().
    public function findTopClientsByPaidOrders(int $limit = 15): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id', 'u.email', 'u.accountnumber', 'COUNT(d.id) as totalCommandes')
            ->join('u.documents', 'd')
            ->join('d.payment', 'p')
            ->andWhere('p.timeOfTransaction IS NOT NULL')
            ->andWhere('u.email != :clientDePassage')
            ->setParameter('clientDePassage', 'client_de_passage@refaitesvosjeux.fr')
            ->groupBy('u.id')
            ->orderBy('totalCommandes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findClientDePassageOrderCount(): ?array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id', 'u.email', 'COUNT(d.id) as totalCommandes')
            ->join('u.documents', 'd')
            ->join('d.payment', 'p')
            ->andWhere('p.timeOfTransaction IS NOT NULL')
            ->andWhere('u.email = :clientDePassage')
            ->setParameter('clientDePassage', 'client_de_passage@refaitesvosjeux.fr')
            ->groupBy('u.id')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Comptes clients inactifs : jamais commandé, au plus une adresse, aucun panier/devis
     * de structure en cours, pas revenus depuis plus d'un mois, et jamais bénévole/admin.
     *
     * @return User[]
     */
    public function findInactiveAccountsToDelete(): array
    {
        $oneMonthAgo = new \DateTimeImmutable('-1 month');

        $qb = $this->createQueryBuilder('u');

        return $qb
            ->where('u.lastvisite < :oneMonthAgo')
            ->andWhere('u.roles NOT LIKE :adminRole')
            ->andWhere('u.roles NOT LIKE :superAdminRole')
            ->andWhere($qb->expr()->not($qb->expr()->exists(
                'SELECT 1 FROM App\Entity\Document d WHERE d.user = u'
            )))
            ->andWhere($qb->expr()->not($qb->expr()->exists(
                'SELECT 1 FROM App\Entity\Panier p WHERE p.user = u'
            )))
            ->andWhere($qb->expr()->not($qb->expr()->exists(
                'SELECT 1 FROM App\Entity\QuoteRequest qr WHERE qr.user = u'
            )))
            ->andWhere('(SELECT COUNT(a.id) FROM App\Entity\Address a WHERE a.user = u) <= 1')
            ->setParameter('oneMonthAgo', $oneMonthAgo)
            ->setParameter('adminRole', '%"ROLE_ADMIN"%')
            ->setParameter('superAdminRole', '%"ROLE_SUPER_ADMIN"%')
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
