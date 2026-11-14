<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TicketCategory;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<TicketCategory> */
final class TicketCategoryRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketCategory::class);
    }

    public function findActiveByCode(string $code): ?TicketCategory
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')
            ->andWhere('c.isActive = :active')
            ->setParameter('code', strtolower(trim($code)))
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<TicketCategory> */
    public function findActiveRoots(): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('children')
            ->leftJoin('c.children', 'children', 'WITH', 'children.isActive = :active')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('children.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<TicketCategory> */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
