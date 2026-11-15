<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Dto\Input\VerifyEmailInput;
use App\State\Processor\VerifyEmailProcessor;

#[ApiResource(
    shortName: 'VerifyEmail',
    operations: [
        new Post(
            uriTemplate: '/auth/verify-email',
            uriVariables: [],
            input: VerifyEmailInput::class,
            output: AuthActionResult::class,
            processor: VerifyEmailProcessor::class,
            read: false,
            status: 200,
            security: "is_granted('PUBLIC_ACCESS')",
            description: 'Consumes a single-use email verification token.',
        ),
    ],
)]
final class VerifyEmailResource
{
}
