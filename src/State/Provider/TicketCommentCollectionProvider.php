<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\TicketCommentResource;
use App\Entity\TicketComment;
use App\Repository\TicketCommentRepository;
use App\Service\Api\ApiPaginator;
use App\Service\Api\TicketAccessService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<TicketCommentResource> */
final readonly class TicketCommentCollectionProvider implements ProviderInterface
{
    public function __construct(
        private TicketCommentRepository $comments,
        private TicketAccessService $ticketAccess,
        private ApiPaginator $paginator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $ticketId = $uriVariables['ticketId'] ?? null;
        if (!is_string($ticketId) || $ticketId === '') {
            throw new NotFoundHttpException('Ticket not found.');
        }
        $ticket = $this->ticketAccess->getVisibleTicket($ticketId);

        $qb = $this->comments->createQueryBuilder('c')
            ->innerJoin('c.author', 'author')->addSelect('author')
            ->andWhere('c.ticket = :ticket')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('ticket', $ticket)
            ->orderBy('c.createdAt', 'ASC')
            ->addOrderBy('c.id', 'ASC');

        if (!$this->ticketAccess->canSeeInternalNotes()) {
            $qb->andWhere('c.visibility = :visibility')->setParameter('visibility', 'public');
        }

        return $this->paginator->paginate(
            $qb,
            $context,
            static fn (TicketComment $comment): TicketCommentResource => TicketCommentResource::fromEntity($comment),
            50,
        );
    }
}
