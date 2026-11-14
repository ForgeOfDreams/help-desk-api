<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<User> */
final class UserRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findActiveByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->andWhere('u.isActive = :active')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('email', strtolower(trim($email)))
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<User> */
    public function searchActive(string $term, int $limit = 20): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->andWhere('u.deletedAt IS NULL')
            ->andWhere('(LOWER(u.email) LIKE :term OR LOWER(u.displayName) LIKE :term)')
            ->setParameter('active', true)
            ->setParameter('term', '%' . strtolower($term) . '%')
            ->orderBy('u.displayName', 'ASC')
            ->addOrderBy('u.email', 'ASC')
            ->setMaxResults($this->normalizeLimit($limit))
            ->getQuery()
            ->getResult();
    }

}
