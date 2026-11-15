<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template TEntity of object
 *
 * @extends ServiceEntityRepository<TEntity>
 */
abstract class AbstractRepository extends ServiceEntityRepository
{
    public function save(object $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(object $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    protected function normalizeLimit(int $limit, int $maximum = 200): int
    {
        return max(1, min($limit, $maximum));
    }
}
