<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\TicketCategory;
use App\State\Provider\TicketCategoryCollectionProvider;
use App\State\Provider\TicketCategoryItemProvider;

#[ApiResource(
    shortName: 'TicketCategory',
    operations: [
        new GetCollection(
            uriTemplate: '/ticket-categories',
            provider: TicketCategoryCollectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            paginationEnabled: true,
            paginationItemsPerPage: 50,
            paginationMaximumItemsPerPage: 100,
        ),
        new Get(
            uriTemplate: '/ticket-categories/{id}',
            provider: TicketCategoryItemProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
)]
final class TicketCategoryResource
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;
    public string $name = '';
    public string $code = '';
    public ?string $description = null;
    public ?string $parentId = null;
    public bool $isActive = true;

    public static function fromEntity(TicketCategory $category): self
    {
        $resource = new self();
        $resource->id = $category->getId()?->toRfc4122();
        $resource->name = $category->getName();
        $resource->code = $category->getCode();
        $resource->description = $category->getDescription();
        $resource->parentId = $category->getParent()?->getId()?->toRfc4122();
        $resource->isActive = $category->isActive();

        return $resource;
    }
}
