<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<Notification> */
final class NotificationRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /** @return list<Notification> */
    public function findUnreadForUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('n')
            ->addSelect('ticket')
            ->leftJoin('n.ticket', 'ticket')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($this->normalizeLimit($limit, 200))
            ->getQuery()
            ->getResult();
    }

    public function countUnreadForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
