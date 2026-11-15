<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Dto\Input\ChangeOwnPasswordInput;
use App\State\Processor\ChangeOwnPasswordProcessor;

#[ApiResource(
    shortName: 'ChangeOwnPassword',
    operations: [
        new Post(
            uriTemplate: '/me/change-password',
            uriVariables: [],
            input: ChangeOwnPasswordInput::class,
            output: AuthActionResult::class,
            processor: ChangeOwnPasswordProcessor::class,
            read: false,
            status: 200,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            description: 'Changes the current authenticated user password after checking the current password.',
        ),
    ],
)]
final class ChangeOwnPasswordResource
{
}
