<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use App\Dto\Input\AssignTicketInput;
use App\Dto\Input\ChangeTicketStatusInput;
use App\Dto\Input\CreateTicketInput;
use App\Entity\Ticket;
use App\State\Processor\AssignTicketProcessor;
use App\State\Processor\ChangeTicketStatusProcessor;
use App\State\Processor\CreateTicketProcessor;
use App\State\Provider\TicketCollectionProvider;
use App\State\Provider\TicketItemProvider;

#[ApiResource(
    shortName: 'Ticket',
    operations: [
        new GetCollection(
            uriTemplate: '/tickets',
            provider: TicketCollectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            paginationEnabled: true,
            paginationClientEnabled: true,
            paginationClientItemsPerPage: true,
            paginationItemsPerPage: 25,
            paginationMaximumItemsPerPage: 100,
            parameters: [
                'status' => new QueryParameter(
                    description: 'Filter tickets by status.',
                    schema: ['type' => 'string', 'enum' => ['new', 'open', 'in_progress', 'pending', 'resolved', 'closed']],
                ),
                'priority' => new QueryParameter(
                    description: 'Filter tickets by priority.',
                    schema: ['type' => 'string', 'enum' => ['low', 'normal', 'high', 'urgent']],
                ),
            ],
        ),
        new Post(
            uriTemplate: '/tickets',
            input: CreateTicketInput::class,
            output: self::class,
            processor: CreateTicketProcessor::class,
            security: "is_granted('ROLE_USER')",
            read: false,
            status: 201,
        ),
        new Get(
            uriTemplate: '/tickets/{id}',
            provider: TicketItemProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Patch(
            uriTemplate: '/tickets/{id}/status',
            uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])],
            input: ChangeTicketStatusInput::class,
            output: self::class,
            processor: ChangeTicketStatusProcessor::class,
            security: "is_granted('ROLE_AGENT')",
            read: false,
            name: 'ticket_change_status',
        ),
        new Patch(
            uriTemplate: '/tickets/{id}/assignment',
            uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])],
            input: AssignTicketInput::class,
            output: self::class,
            processor: AssignTicketProcessor::class,
            security: "is_granted('ROLE_AGENT')",
            read: false,
            name: 'ticket_assign',
        ),
    ],
)]
final class TicketResource
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    public ?string $ticketNumber = null;
    public string $subject = '';
    public string $description = '';
    public string $status = 'new';
    public string $priority = 'normal';

    public ?string $requesterId = null;
    public ?string $requesterName = null;
    public ?string $assigneeId = null;
    public ?string $assigneeName = null;
    public ?string $teamId = null;
    public ?string $teamName = null;
    public ?string $categoryId = null;
    public ?string $categoryName = null;

    public ?\DateTimeImmutable $firstResponseAt = null;
    public ?\DateTimeImmutable $resolvedAt = null;
    public ?\DateTimeImmutable $closedAt = null;
    public ?\DateTimeImmutable $createdAt = null;
    public ?\DateTimeImmutable $updatedAt = null;
    public int $version = 1;

    public static function fromEntity(Ticket $ticket): self
    {
        $resource = new self();
        $resource->id = $ticket->getId()?->toRfc4122();
        $resource->ticketNumber = $ticket->getTicketNumber();
        $resource->subject = $ticket->getSubject();
        $resource->description = $ticket->getDescription();
        $resource->status = $ticket->getStatus()->value;
        $resource->priority = $ticket->getPriority()->value;

        $requester = $ticket->getRequester();
        $resource->requesterId = $requester?->getId()?->toRfc4122();
        $resource->requesterName = $requester?->getDisplayName();

        $assignee = $ticket->getAssignee();
        $resource->assigneeId = $assignee?->getId()?->toRfc4122();
        $resource->assigneeName = $assignee?->getDisplayName();

        $team = $ticket->getTeam();
        $resource->teamId = $team?->getId()?->toRfc4122();
        $resource->teamName = $team?->getName();

        $category = $ticket->getCategory();
        $resource->categoryId = $category?->getId()?->toRfc4122();
        $resource->categoryName = $category?->getName();

        $resource->firstResponseAt = $ticket->getFirstResponseAt();
        $resource->resolvedAt = $ticket->getResolvedAt();
        $resource->closedAt = $ticket->getClosedAt();
        $resource->createdAt = $ticket->getCreatedAt();
        $resource->updatedAt = $ticket->getUpdatedAt();
        $resource->version = $ticket->getVersion();

        return $resource;
    }
}
