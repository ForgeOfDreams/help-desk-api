<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\TicketCategoryResource;
use App\Entity\TicketCategory;
use App\Repository\TicketCategoryRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/** @implements ProviderInterface<TicketCategoryResource> */
final readonly class TicketCategoryItemProvider implements ProviderInterface
{
    public function __construct(private TicketCategoryRepository $categories)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $id = $uriVariables['id'] ?? null;
        try {
            $uuid = is_string($id) ? Uuid::fromString($id) : null;
        } catch (\InvalidArgumentException) {
            $uuid = null;
        }

        $category = null === $uuid ? null : $this->categories->find($uuid);
        if (!$category instanceof TicketCategory || !$category->isActive()) {
            throw new NotFoundHttpException('Ticket category not found.');
        }

        return TicketCategoryResource::fromEntity($category);
    }
}
