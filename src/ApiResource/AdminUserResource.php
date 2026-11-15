<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Dto\Input\CreateManagedUserInput;
use App\State\Processor\CreateManagedUserProcessor;

#[ApiResource(
    shortName: 'ManagedUser',
    operations: [
        new Post(
            uriTemplate: '/admin/users',
            uriVariables: [],
            input: CreateManagedUserInput::class,
            output: ManagedUserView::class,
            processor: CreateManagedUserProcessor::class,
            read: false,
            status: 201,
            security: "is_granted('ROLE_ADMIN')",
            description: 'Invites a user or support agent. ROLE_ADMIN can only be granted from a trusted CLI/deployment process.',
        ),
    ],
)]
final class AdminUserResource
{
}
