<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\AuthActionResult;
use App\Dto\Input\ResendVerificationInput;
use App\Service\Auth\AuthRateLimit;
use App\Service\Auth\UserOnboardingService;

/** @implements ProcessorInterface<ResendVerificationInput, AuthActionResult> */
final readonly class ResendVerificationProcessor implements ProcessorInterface
{
    public function __construct(
        private UserOnboardingService $onboarding,
        private AuthRateLimit $rateLimit,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthActionResult
    {
        if (!$data instanceof ResendVerificationInput) {
            throw new \UnexpectedValueException('Expected ResendVerificationInput.');
        }

        $this->rateLimit->consumeResendAttempt();
        $this->onboarding->resendInstructions($data->email);

        return new AuthActionResult('If an inactive account exists for this address, new instructions will be sent.');
    }
}
