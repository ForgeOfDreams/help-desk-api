<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\TicketResource;
use App\Dto\Input\CreateTicketInput;
use App\Entity\Ticket;
use App\Entity\TicketHistory;
use App\Entity\TicketStatusHistory;
use App\Enum\TicketPriority;
use App\Repository\TicketCategoryRepository;
use App\Repository\TicketNumberCounterRepository;
use App\Service\Api\TicketAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<CreateTicketInput, TicketResource> */
final readonly class CreateTicketProcessor implements ProcessorInterface
{
    public function __construct(
        private TicketAccessService $access,
        private TicketCategoryRepository $categories,
        private TicketNumberCounterRepository $ticketNumbers,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof CreateTicketInput) {
            throw new \UnexpectedValueException('Expected CreateTicketInput.');
        }

        $requester = $this->access->currentUser();
        $category = null;
        if (null !== $data->categoryId && '' !== $data->categoryId) {
            try {
                $categoryId = Uuid::fromString($data->categoryId);
            } catch (\InvalidArgumentException) {
                throw new NotFoundHttpException('Ticket category not found.');
            }
            $category = $this->categories->find($categoryId);
            if (null === $category || !$category->isActive()) {
                throw new NotFoundHttpException('Ticket category not found.');
            }
        }

        $ticket = (new Ticket())
            ->setRequester($requester)
            ->setSubject($data->subject)
            ->setDescription($data->description)
            ->setPriority(TicketPriority::from($data->priority))
            ->setCategory($category);

        return $this->entityManager->wrapInTransaction(function () use ($ticket, $requester): TicketResource {
            $ticket->assignTicketNumber($this->ticketNumbers->nextValue());
            $this->entityManager->persist($ticket);

            $statusHistory = (new TicketStatusHistory())
                ->setTicket($ticket)
                ->setChangedBy($requester)
                ->setOldStatus(null)
                ->setNewStatus($ticket->getStatus())
                ->setReason('Ticket created');

            $history = (new TicketHistory())
                ->setTicket($ticket)
                ->setActor($requester)
                ->setAction('ticket.created')
                ->setChanges([
                    'status' => ['old' => null, 'new' => $ticket->getStatus()->value],
                    'priority' => ['old' => null, 'new' => $ticket->getPriority()->value],
                ]);

            $this->entityManager->persist($statusHistory);
            $this->entityManager->persist($history);
            $this->entityManager->flush();

            return TicketResource::fromEntity($ticket);
        });
    }
}
