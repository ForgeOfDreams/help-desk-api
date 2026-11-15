<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\AuthActionResult;
use App\Dto\Input\RegisterUserInput;
use App\Service\Auth\AuthRateLimit;
use App\Service\Auth\UserOnboardingService;

/** @implements ProcessorInterface<RegisterUserInput, AuthActionResult> */
final readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(
        private UserOnboardingService $onboarding,
        private AuthRateLimit $rateLimit,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthActionResult
    {
        if (!$data instanceof RegisterUserInput) {
            throw new \UnexpectedValueException('Expected RegisterUserInput.');
        }

        $this->rateLimit->consumeRegistrationAttempt();
        $this->onboarding->register($data->email, $data->displayName, $data->password);

        return new AuthActionResult('If this address can be registered, email instructions will be sent.');
    }
}
