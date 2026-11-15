<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\TicketCommentResource;
use App\Entity\TicketComment;
use App\Repository\TicketCommentRepository;
use App\Service\Api\TicketAccessService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/** @implements ProviderInterface<TicketCommentResource> */
final readonly class TicketCommentItemProvider implements ProviderInterface
{
    public function __construct(
        private TicketCommentRepository $comments,
        private TicketAccessService $ticketAccess,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $id = $uriVariables['id'] ?? null;
        try {
            $uuid = is_string($id) ? Uuid::fromString($id) : null;
        } catch (\InvalidArgumentException) {
            $uuid = null;
        }

        $comment = null === $uuid ? null : $this->comments->find($uuid);
        if (!$comment instanceof TicketComment || null !== $comment->getDeletedAt() || null === $comment->getTicket()) {
            throw new NotFoundHttpException('Comment not found.');
        }

        $this->ticketAccess->assertCanView($comment->getTicket());
        if ('internal' === $comment->getVisibility()->value && !$this->ticketAccess->canSeeInternalNotes()) {
            throw new NotFoundHttpException('Comment not found.');
        }

        return TicketCommentResource::fromEntity($comment);
    }
}
