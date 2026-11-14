<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\TicketStatusHistory;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<TicketStatusHistory> */
final class TicketStatusHistoryRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketStatusHistory::class);
    }

    /** @return list<TicketStatusHistory> */
    public function findForTicket(Ticket $ticket): array
    {
        return $this->createQueryBuilder('h')
            ->addSelect('actor')
            ->leftJoin('h.changedBy', 'actor')
            ->andWhere('h.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('h.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
