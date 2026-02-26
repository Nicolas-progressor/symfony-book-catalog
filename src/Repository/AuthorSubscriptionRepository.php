<?php

namespace App\Repository;

use App\Entity\AuthorSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthorSubscription>
 */
class AuthorSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthorSubscription::class);
    }

    public function findByUserAndAuthor(int $userId, int $authorId): ?AuthorSubscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :userId')
            ->andWhere('s.author = :authorId')
            ->setParameter('userId', $userId)
            ->setParameter('authorId', $authorId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findSubscribersByAuthor(int $authorId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.author = :authorId')
            ->setParameter('authorId', $authorId)
            ->getQuery()
            ->getResult();
    }
}
