<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Input\ChangeTicketStatusInput;
use App\ApiResource\TicketResource;
use App\Entity\TicketHistory;
use App\Entity\TicketStatusHistory;
use App\Enum\TicketStatus;
use App\Service\Api\TicketAccessService;
use App\Service\Api\TicketStatusTransitionPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UnexpectedValueException;

/** @implements ProcessorInterface<ChangeTicketStatusInput, TicketResource> */
final readonly class ChangeTicketStatusProcessor implements ProcessorInterface
{
    public function __construct(
        private TicketAccessService $ticketAccess,
        private TicketStatusTransitionPolicy $transitions,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof ChangeTicketStatusInput) {
            throw new UnexpectedValueException('Expected ChangeTicketStatusInput.');
        }

        $id = $uriVariables['id'] ?? null;
        if (!is_string($id) || $id === '') {
            throw new BadRequestHttpException('Ticket identifier is required.');
        }

        $ticket = $this->ticketAccess->getVisibleTicket($id);
        $this->ticketAccess->assertCanManage($ticket);
        $newStatus = TicketStatus::tryFrom($data->status);
        if ($newStatus === null) {
            throw new BadRequestHttpException('Unsupported ticket status.');
        }

        $oldStatus = $ticket->getStatus();
        $this->transitions->assertAllowed($oldStatus, $newStatus);
        if ($oldStatus === $newStatus) {
            return TicketResource::fromEntity($ticket);
        }

        $actor = $this->ticketAccess->currentUser();

        return $this->entityManager->wrapInTransaction(function () use ($ticket, $oldStatus, $newStatus, $data, $actor): TicketResource {
            $ticket->setStatus($newStatus);

            $statusHistory = (new TicketStatusHistory())
                ->setTicket($ticket)
                ->setChangedBy($actor)
                ->setOldStatus($oldStatus)
                ->setNewStatus($newStatus)
                ->setReason($data->reason);

            $history = (new TicketHistory())
                ->setTicket($ticket)
                ->setActor($actor)
                ->setAction('ticket.status_changed')
                ->setChanges([
                    'status' => ['old' => $oldStatus->value, 'new' => $newStatus->value],
                    'reason' => $data->reason,
                ]);

            $this->entityManager->persist($statusHistory);
            $this->entityManager->persist($history);
            $this->entityManager->flush();

            return TicketResource::fromEntity($ticket);
        });
    }
}
