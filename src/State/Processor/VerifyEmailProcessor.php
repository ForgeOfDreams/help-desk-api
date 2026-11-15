<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\AuthActionResult;
use App\Dto\Input\VerifyEmailInput;
use App\Service\Auth\AuthRateLimit;
use App\Service\Auth\UserOnboardingService;

/** @implements ProcessorInterface<VerifyEmailInput, AuthActionResult> */
final readonly class VerifyEmailProcessor implements ProcessorInterface
{
    public function __construct(
        private UserOnboardingService $onboarding,
        private AuthRateLimit $rateLimit,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthActionResult
    {
        if (!$data instanceof VerifyEmailInput) {
            throw new \UnexpectedValueException('Expected VerifyEmailInput.');
        }

        $this->rateLimit->consumeTokenCompletionAttempt();
        $this->onboarding->verifyEmail($data->token);

        return new AuthActionResult('Email confirmed. You can now sign in.');
    }
}
