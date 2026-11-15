<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\TicketCommentResource;
use App\Dto\Input\CreateTicketCommentInput;
use App\Entity\TicketComment;
use App\Entity\TicketHistory;
use App\Enum\CommentVisibility;
use App\Service\Api\TicketAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProcessorInterface<CreateTicketCommentInput, TicketCommentResource> */
final readonly class CreateTicketCommentProcessor implements ProcessorInterface
{
    public function __construct(
        private TicketAccessService $ticketAccess,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof CreateTicketCommentInput) {
            throw new \UnexpectedValueException('Expected CreateTicketCommentInput.');
        }
        $ticketId = $uriVariables['ticketId'] ?? null;
        if (!is_string($ticketId) || '' === $ticketId) {
            throw new BadRequestHttpException('Ticket identifier is required.');
        }

        $ticket = $this->ticketAccess->getVisibleTicket($ticketId);
        $author = $this->ticketAccess->currentUser();
        $visibility = CommentVisibility::tryFrom($data->visibility);
        if (null === $visibility) {
            throw new BadRequestHttpException('Unsupported comment visibility.');
        }
        if (CommentVisibility::Internal === $visibility && !$this->ticketAccess->canSeeInternalNotes()) {
            throw new AccessDeniedHttpException('Only support agents can create internal notes.');
        }

        $comment = (new TicketComment())
            ->setTicket($ticket)
            ->setAuthor($author)
            ->setBody($data->body)
            ->setVisibility($visibility);

        return $this->entityManager->wrapInTransaction(function () use ($ticket, $comment, $author, $visibility): TicketCommentResource {
            if ($this->ticketAccess->canSeeInternalNotes() && CommentVisibility::Public === $visibility) {
                $ticket->markFirstResponse();
            }

            $history = (new TicketHistory())
                ->setTicket($ticket)
                ->setActor($author)
                ->setAction('ticket.comment_created')
                ->setChanges(['visibility' => ['new' => $visibility->value]]);

            $this->entityManager->persist($comment);
            $this->entityManager->persist($history);
            $this->entityManager->flush();

            return TicketCommentResource::fromEntity($comment);
        });
    }
}
