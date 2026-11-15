<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\TicketResource;
use App\Entity\Team;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use App\Service\Api\ApiPaginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProviderInterface<TicketResource> */
final readonly class TicketCollectionProvider implements ProviderInterface
{
    public function __construct(
        private TicketRepository $tickets,
        private Security $security,
        private ApiPaginator $paginator,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $qb = $this->tickets->createQueryBuilder('t')
            ->addSelect('requester', 'assignee', 'team', 'category')
            ->leftJoin('t.requester', 'requester')
            ->leftJoin('t.assignee', 'assignee')
            ->leftJoin('t.team', 'team')
            ->leftJoin('t.category', 'category')
            ->orderBy('t.updatedAt', 'DESC')
            ->addOrderBy('t.id', 'DESC');

        if ($this->security->isGranted('ROLE_ADMIN')) {
            // Administrators can see all tickets.
        } elseif ($this->security->isGranted('ROLE_AGENT')) {
            $teamMemberships = $user->getTeamMemberships();
            $teams = [];
            foreach ($teamMemberships as $membership) {
                $team = $membership->getTeam();
                if ($team instanceof Team && $team->isActive()) {
                    $teams[] = $team;
                }
            }

            $accessConditions = ['t.requester = :viewer', 't.assignee = :viewer', '(t.team IS NULL AND t.assignee IS NULL)'];
            $qb->setParameter('viewer', $user);
            if ([] !== $teams) {
                $accessConditions[] = 't.team IN (:viewerTeams)';
                $qb->setParameter('viewerTeams', $teams);
            }
            // Group OR branches so status/priority filters apply to every accessible ticket.
            $qb->andWhere('('.implode(' OR ', $accessConditions).')');
        } else {
            $qb->andWhere('t.requester = :requester')->setParameter('requester', $user);
        }

        $requestQuery = $this->requestStack->getCurrentRequest()?->query->all() ?? [];
        $filters = array_merge($requestQuery, $context['filters'] ?? []);
        if (isset($filters['status']) && '' !== $filters['status']) {
            $status = TicketStatus::tryFrom((string) $filters['status']);
            if (null === $status) {
                throw new BadRequestHttpException('Unsupported ticket status filter.');
            }
            $qb->andWhere('t.status = :status')->setParameter('status', $status->value);
        }
        if (isset($filters['priority']) && '' !== $filters['priority']) {
            $priority = TicketPriority::tryFrom((string) $filters['priority']);
            if (null === $priority) {
                throw new BadRequestHttpException('Unsupported ticket priority filter.');
            }
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $priority->value);
        }

        /** @var PaginatorInterface $result */
        $result = $this->paginator->paginate(
            $qb,
            $context,
            static fn (Ticket $ticket): TicketResource => TicketResource::fromEntity($ticket),
            25,
        );

        return $result;
    }
}
