<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\NotificationDelivery;
use App\Enum\NotificationDeliveryStatus;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<NotificationDelivery> */
final class NotificationDeliveryRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationDelivery::class);
    }

    /** @return list<NotificationDelivery> */
    public function findPending(int $limit = 100): array
    {
        return $this->createQueryBuilder('d')
            ->addSelect('n')
            ->innerJoin('d.notification', 'n')
            ->andWhere('d.status = :status')
            ->setParameter('status', NotificationDeliveryStatus::Pending->value)
            ->orderBy('d.createdAt', 'ASC')
            ->setMaxResults($this->normalizeLimit($limit, 500))
            ->getQuery()
            ->getResult();
    }
}
