<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Team;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<Team> */
final class TeamRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function findActiveByCode(string $code): ?Team
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.code = :code')
            ->andWhere('t.isActive = :active')
            ->setParameter('code', strtolower(trim($code)))
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<Team> */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
