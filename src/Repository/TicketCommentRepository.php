<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\TicketComment;
use App\Enum\CommentVisibility;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<TicketComment> */
final class TicketCommentRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketComment::class);
    }

    /**
     * The caller must pass $includeInternal=true only after the current user has been authorized to view internal notes.
     *
     * @return list<TicketComment>
     */
    public function findVisibleForTicket(Ticket $ticket, bool $includeInternal = false, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('c')
            ->addSelect('author')
            ->innerJoin('c.author', 'author')
            ->andWhere('c.ticket = :ticket')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('ticket', $ticket)
            ->orderBy('c.createdAt', 'ASC')
            ->setMaxResults($this->normalizeLimit($limit, 500));

        if (!$includeInternal) {
            $qb->andWhere('c.visibility = :visibility')
                ->setParameter('visibility', CommentVisibility::Public->value);
        }

        return $qb->getQuery()->getResult();
    }
}
