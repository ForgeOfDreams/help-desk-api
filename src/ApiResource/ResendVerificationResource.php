<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Dto\Input\ResendVerificationInput;
use App\State\Processor\ResendVerificationProcessor;

#[ApiResource(
    shortName: 'ResendVerification',
    operations: [
        new Post(
            uriTemplate: '/auth/resend-verification',
            uriVariables: [],
            input: ResendVerificationInput::class,
            output: AuthActionResult::class,
            processor: ResendVerificationProcessor::class,
            read: false,
            status: 202,
            security: "is_granted('PUBLIC_ACCESS')",
            description: 'Resends onboarding instructions without revealing whether an account exists.',
        ),
    ],
)]
final class ResendVerificationResource
{
}
