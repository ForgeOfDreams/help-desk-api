<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Dto\Input\SetInitialPasswordInput;
use App\State\Processor\SetInitialPasswordProcessor;

#[ApiResource(
    shortName: 'SetInitialPassword',
    operations: [
        new Post(
            uriTemplate: '/auth/set-password',
            uriVariables: [],
            input: SetInitialPasswordInput::class,
            output: AuthActionResult::class,
            processor: SetInitialPasswordProcessor::class,
            read: false,
            status: 200,
            security: "is_granted('PUBLIC_ACCESS')",
            description: 'Sets the first password for an invited account using a single-use invitation token.',
        ),
    ],
)]
final class SetInitialPasswordResource
{
}
