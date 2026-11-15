<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\TicketHistory;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<TicketHistory> */
final class TicketHistoryRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketHistory::class);
    }

    /** @return list<TicketHistory> */
    public function findForTicket(Ticket $ticket, int $limit = 200): array
    {
        return $this->createQueryBuilder('h')
            ->addSelect('actor')
            ->leftJoin('h.actor', 'actor')
            ->andWhere('h.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($this->normalizeLimit($limit, 500))
            ->getQuery()
            ->getResult();
    }
}
