<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\TicketAttachment;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<TicketAttachment> */
final class TicketAttachmentRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketAttachment::class);
    }

    /** @return list<TicketAttachment> */
    public function findForTicket(Ticket $ticket): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('u')
            ->innerJoin('a.uploadedBy', 'u')
            ->andWhere('a.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
