<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<Role> */
final class RoleRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function findByCode(string $code): ?Role
    {
        return $this->findOneBy(['code' => strtoupper(trim($code))]);
    }

    /** @return list<Role> */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
