<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\TicketWatcher;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<TicketWatcher> */
final class TicketWatcherRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketWatcher::class);
    }

    public function isWatching(Ticket $ticket, User $user): bool
    {
        return $this->count(['ticket' => $ticket, 'user' => $user]) > 0;
    }

    /** @return list<TicketWatcher> */
    public function findForTicket(Ticket $ticket): array
    {
        return $this->createQueryBuilder('w')
            ->addSelect('u')
            ->innerJoin('w.user', 'u')
            ->andWhere('w.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('u.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
