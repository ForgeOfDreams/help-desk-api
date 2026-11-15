<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use App\Entity\Notification;
use App\State\Processor\MarkNotificationReadProcessor;
use App\State\Provider\NotificationCollectionProvider;
use App\State\Provider\NotificationItemProvider;
use DateTimeImmutable;

#[ApiResource(
    shortName: 'Notification',
    operations: [
        new GetCollection(
            uriTemplate: '/notifications',
            provider: NotificationCollectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            paginationEnabled: true,
            paginationClientEnabled: true,
            paginationClientItemsPerPage: true,
            paginationItemsPerPage: 25,
            paginationMaximumItemsPerPage: 100,
            parameters: [
                'unread' => new QueryParameter(
                    description: 'Return only unread notifications when true.',
                    schema: ['type' => 'boolean'],
                    castToNativeType: true,
                ),
            ],
        ),
        new Get(
            uriTemplate: '/notifications/{id}',
            provider: NotificationItemProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/notifications/{id}/read',
            uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id'])],
            input: false,
            output: self::class,
            processor: MarkNotificationReadProcessor::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'notification_mark_read',
        ),
    ],
)]
final class NotificationResource
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;
    public ?string $ticketId = null;
    public string $type = '';
    /** @var array<string, mixed> */
    public array $payload = [];
    public bool $isRead = false;
    public ?DateTimeImmutable $readAt = null;
    public ?DateTimeImmutable $createdAt = null;

    public static function fromEntity(Notification $notification): self
    {
        $resource = new self();
        $resource->id = $notification->getId()?->toRfc4122();
        $resource->ticketId = $notification->getTicket()?->getId()?->toRfc4122();
        $resource->type = $notification->getType()->value;
        $resource->payload = $notification->getPayload();
        $resource->isRead = $notification->isRead();
        $resource->readAt = $notification->getReadAt();
        $resource->createdAt = $notification->getCreatedAt();

        return $resource;
    }
}
