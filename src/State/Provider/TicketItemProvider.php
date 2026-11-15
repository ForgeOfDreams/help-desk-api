<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\TicketResource;
use App\Service\Api\TicketAccessService;

/** @implements ProviderInterface<TicketResource> */
final readonly class TicketItemProvider implements ProviderInterface
{
    public function __construct(private TicketAccessService $access)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $id = $uriVariables['id'] ?? null;
        if (!is_string($id) || $id === '') {
            return null;
        }

        return TicketResource::fromEntity($this->access->getVisibleTicket($id));
    }
}
