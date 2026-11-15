<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Team;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<Ticket> */
final class TicketRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function findByTicketNumber(string|int $ticketNumber): ?Ticket
    {
        return $this->findOneBy(['ticketNumber' => (string) $ticketNumber]);
    }

    /** @return list<Ticket> */
    public function findForRequester(User $requester, ?TicketStatus $status = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('assignee', 'team')
            ->leftJoin('t.assignee', 'assignee')
            ->leftJoin('t.team', 'team')
            ->andWhere('t.requester = :requester')
            ->setParameter('requester', $requester)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($this->normalizeLimit($limit));

        if (null !== $status) {
            $qb->andWhere('t.status = :status')->setParameter('status', $status->value);
        }

        return $qb->getQuery()->getResult();
    }

    /** @return list<Ticket> */
    public function findAssignedTo(User $assignee, ?TicketStatus $status = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('requester', 'team')
            ->leftJoin('t.requester', 'requester')
            ->leftJoin('t.team', 'team')
            ->andWhere('t.assignee = :assignee')
            ->setParameter('assignee', $assignee)
            ->orderBy('t.updatedAt', 'DESC')
            ->setMaxResults($this->normalizeLimit($limit));

        if (null !== $status) {
            $qb->andWhere('t.status = :status')->setParameter('status', $status->value);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Fetches the support queue with urgent/high priorities first and most recently changed tickets next.
     * Authorization and team-membership checks must happen before calling this method.
     *
     * @return list<Ticket>
     */
    public function findQueue(
        ?Team $team = null,
        ?TicketStatus $status = null,
        ?TicketPriority $priority = null,
        int $limit = 50,
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('requester', 'assignee', 'team')
            ->leftJoin('t.requester', 'requester')
            ->leftJoin('t.assignee', 'assignee')
            ->leftJoin('t.team', 'team')
            ->addSelect(
                "CASE t.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END AS HIDDEN priorityRank"
            )
            ->orderBy('priorityRank', 'ASC')
            ->addOrderBy('t.updatedAt', 'DESC')
            ->setMaxResults($this->normalizeLimit($limit));

        if (null !== $team) {
            $qb->andWhere('t.team = :team')->setParameter('team', $team);
        }
        if (null !== $status) {
            $qb->andWhere('t.status = :status')->setParameter('status', $status->value);
        }
        if (null !== $priority) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $priority->value);
        }

        return $qb->getQuery()->getResult();
    }

    public function countOpenForAssignee(User $assignee): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.assignee = :assignee')
            ->andWhere('t.status NOT IN (:finished)')
            ->setParameter('assignee', $assignee)
            ->setParameter('finished', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
