<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\TicketResource;
use App\Dto\Input\AssignTicketInput;
use App\Entity\TicketHistory;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Api\TicketAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<AssignTicketInput, TicketResource> */
final readonly class AssignTicketProcessor implements ProcessorInterface
{
    public function __construct(
        private TicketAccessService $ticketAccess,
        private UserRepository $users,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof AssignTicketInput) {
            throw new \UnexpectedValueException('Expected AssignTicketInput.');
        }

        $ticketId = $uriVariables['id'] ?? null;
        if (!is_string($ticketId) || '' === $ticketId) {
            throw new BadRequestHttpException('Ticket identifier is required.');
        }

        try {
            $assigneeId = Uuid::fromString($data->assigneeId);
        } catch (\InvalidArgumentException) {
            throw new BadRequestHttpException('Assignee identifier must be a UUID.');
        }

        $ticket = $this->ticketAccess->getVisibleTicket($ticketId);
        $this->ticketAccess->assertCanManage($ticket);

        $assignee = $this->users->find($assigneeId);
        if (!$assignee instanceof User || !$assignee->isActive() || null !== $assignee->getDeletedAt()) {
            throw new NotFoundHttpException('Assignee not found.');
        }
        if (!array_intersect(['ROLE_AGENT', 'ROLE_ADMIN'], $assignee->getRoles())) {
            throw new BadRequestHttpException('The assignee must have a support-agent role.');
        }

        $previousAssignee = $ticket->getAssignee();
        if ($previousAssignee?->getId()?->toRfc4122() === $assignee->getId()?->toRfc4122()) {
            return TicketResource::fromEntity($ticket);
        }

        $actor = $this->ticketAccess->currentUser();

        return $this->entityManager->wrapInTransaction(function () use ($ticket, $previousAssignee, $assignee, $actor): TicketResource {
            $ticket->setAssignee($assignee);
            $history = (new TicketHistory())
                ->setTicket($ticket)
                ->setActor($actor)
                ->setAction('ticket.assigned')
                ->setChanges([
                    'assignee_id' => [
                        'old' => $previousAssignee?->getId()?->toRfc4122(),
                        'new' => $assignee->getId()?->toRfc4122(),
                    ],
                ]);
            $this->entityManager->persist($history);
            $this->entityManager->flush();

            return TicketResource::fromEntity($ticket);
        });
    }
}
