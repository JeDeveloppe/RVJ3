<?php

namespace App\Repository;

use App\Entity\JobPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobPost>
 *
 * @method JobPost|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobPost|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobPost[]    findAll()
 * @method JobPost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobPost::class);
    }

    /**
     * @return JobPost[] Offres en ligne et dans leur fenêtre de publication
     */
    public function findPublished(): array
    {
        $now = new \DateTimeImmutable('now');

        return $this->createQueryBuilder('j')
            ->andWhere('j.isOnLine = true')
            ->andWhere('j.startPublished <= :now')
            ->andWhere('j.endPublished >= :now')
            ->setParameter('now', $now)
            ->orderBy('j.startPublished', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOnePublished(int $id, string $slug): ?JobPost
    {
        $now = new \DateTimeImmutable('now');

        return $this->createQueryBuilder('j')
            ->andWhere('j.id = :id')
            ->andWhere('j.slug = :slug')
            ->andWhere('j.isOnLine = true')
            ->andWhere('j.startPublished <= :now')
            ->andWhere('j.endPublished >= :now')
            ->setParameter('id', $id)
            ->setParameter('slug', $slug)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
