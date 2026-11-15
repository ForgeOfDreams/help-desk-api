<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Dto\Input\CreateTicketCommentInput;
use App\Entity\TicketComment;
use App\State\Processor\CreateTicketCommentProcessor;
use App\State\Provider\TicketCommentCollectionProvider;
use App\State\Provider\TicketCommentItemProvider;
use DateTimeImmutable;

#[ApiResource(
    shortName: 'TicketComment',
    operations: [
        new GetCollection(
            uriTemplate: '/tickets/{ticketId}/comments',
            uriVariables: ['ticketId' => new Link(fromClass: TicketResource::class, identifiers: ['id'])],
            provider: TicketCommentCollectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            paginationEnabled: true,
            paginationClientEnabled: true,
            paginationClientItemsPerPage: true,
            paginationItemsPerPage: 50,
            paginationMaximumItemsPerPage: 100,
        ),
        new Post(
            uriTemplate: '/tickets/{ticketId}/comments',
            uriVariables: ['ticketId' => new Link(fromClass: TicketResource::class, identifiers: ['id'])],
            input: CreateTicketCommentInput::class,
            output: self::class,
            processor: CreateTicketCommentProcessor::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            status: 201,
        ),
        new Get(
            uriTemplate: '/ticket-comments/{id}',
            provider: TicketCommentItemProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
)]
final class TicketCommentResource
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    public ?string $ticketId = null;
    public ?string $authorId = null;
    public ?string $authorName = null;
    public string $body = '';
    public string $visibility = 'public';
    public ?DateTimeImmutable $createdAt = null;
    public ?DateTimeImmutable $updatedAt = null;

    public static function fromEntity(TicketComment $comment): self
    {
        $resource = new self();
        $resource->id = $comment->getId()?->toRfc4122();
        $resource->ticketId = $comment->getTicket()?->getId()?->toRfc4122();
        $resource->authorId = $comment->getAuthor()?->getId()?->toRfc4122();
        $resource->authorName = $comment->getAuthor()?->getDisplayName();
        $resource->body = $comment->getBody();
        $resource->visibility = $comment->getVisibility()->value;
        $resource->createdAt = $comment->getCreatedAt();
        $resource->updatedAt = $comment->getUpdatedAt();

        return $resource;
    }
}
