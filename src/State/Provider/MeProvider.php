<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\MeResource;
use App\Entity\User;
use App\Service\Api\TicketAccessService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** @implements ProviderInterface<MeResource> */
final readonly class MeProvider implements ProviderInterface
{
    public function __construct(private TicketAccessService $access)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->access->currentUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        $resource = new MeResource();
        $resource->id = $user->getId()?->toRfc4122();
        $resource->email = $user->getEmail();
        $resource->displayName = $user->getDisplayName();
        $resource->roles = $user->getRoles();
        $resource->createdAt = $user->getCreatedAt();

        return $resource;
    }
}
