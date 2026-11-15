<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\AuthActionResult;
use App\Dto\Input\SetInitialPasswordInput;
use App\Service\Auth\AuthRateLimit;
use App\Service\Auth\UserOnboardingService;

/** @implements ProcessorInterface<SetInitialPasswordInput, AuthActionResult> */
final readonly class SetInitialPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private UserOnboardingService $onboarding,
        private AuthRateLimit $rateLimit,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthActionResult
    {
        if (!$data instanceof SetInitialPasswordInput) {
            throw new \UnexpectedValueException('Expected SetInitialPasswordInput.');
        }

        $this->rateLimit->consumeTokenCompletionAttempt();
        $this->onboarding->setInitialPassword($data->token, $data->newPassword);

        return new AuthActionResult('Password set. You can now sign in.');
    }
}
