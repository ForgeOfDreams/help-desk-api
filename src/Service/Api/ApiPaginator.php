<?php

declare(strict_types=1);

namespace App\Service\Api;

use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class ApiPaginator
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * Executes a paginated Doctrine query and maps each result to a public API DTO.
     * The hard limit is enforced server-side even when the client passes itemsPerPage.
     *
     * @param callable(object): object $mapper
     */
    public function paginate(QueryBuilder $queryBuilder, array $context, callable $mapper, int $defaultItemsPerPage = 25): PaginatorInterface
    {
        $requestQuery = $this->requestStack->getCurrentRequest()?->query->all() ?? [];
        $filters = array_merge($requestQuery, $context['filters'] ?? []);
        $page = $this->positiveInteger($filters['page'] ?? 1, 1);
        $itemsPerPage = min(100, $this->positiveInteger($filters['itemsPerPage'] ?? $defaultItemsPerPage, $defaultItemsPerPage));

        $rootAliases = $queryBuilder->getRootAliases();
        $rootAlias = $rootAliases[0] ?? throw new \InvalidArgumentException('The query builder must have a root alias.');

        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder
            ->resetDQLPart('orderBy')
            ->select(sprintf('COUNT(DISTINCT %s.id)', $rootAlias))
            ->setMaxResults(null);

        $totalItems = (int) $countQueryBuilder->getQuery()->getSingleScalarResult();

        $queryBuilder
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        /** @var list<object> $items */
        $items = $queryBuilder->getQuery()->getResult();
        $mappedItems = array_map($mapper, $items);

        return new TraversablePaginator(
            new \ArrayIterator($mappedItems),
            $page,
            $itemsPerPage,
            $totalItems,
        );
    }

    private function positiveInteger(mixed $value, int $fallback): int
    {
        if (false === filter_var($value, FILTER_VALIDATE_INT)) {
            return $fallback;
        }

        return max(1, (int) $value);
    }
}
