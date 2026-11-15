<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Dto\Input\RegisterUserInput;
use App\State\Processor\RegisterUserProcessor;

#[ApiResource(
    shortName: 'RegisterUser',
    operations: [
        new Post(
            uriTemplate: '/auth/register',
            uriVariables: [],
            input: RegisterUserInput::class,
            output: AuthActionResult::class,
            processor: RegisterUserProcessor::class,
            read: false,
            status: 202,
            security: "is_granted('PUBLIC_ACCESS')",
            description: 'Starts public registration and sends email verification instructions when applicable.',
        ),
    ],
)]
final class RegisterUserResource
{
}
