<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\TicketCategoryResource;
use App\Entity\TicketCategory;
use App\Repository\TicketCategoryRepository;
use App\Service\Api\ApiPaginator;

/** @implements ProviderInterface<TicketCategoryResource> */
final readonly class TicketCategoryCollectionProvider implements ProviderInterface
{
    public function __construct(
        private TicketCategoryRepository $categories,
        private ApiPaginator $paginator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $qb = $this->categories->createQueryBuilder('c')
            ->leftJoin('c.parent', 'parent')->addSelect('parent')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('c.id', 'ASC');

        return $this->paginator->paginate(
            $qb,
            $context,
            static fn (TicketCategory $category): TicketCategoryResource => TicketCategoryResource::fromEntity($category),
            50,
        );
    }
}
