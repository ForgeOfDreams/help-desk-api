<?php

declare(strict_types=1);

namespace App\Service\Auth;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final readonly class AuthRateLimit
{
    public function __construct(
        #[Target('auth_registration')]
        private RateLimiterFactoryInterface $registrationLimiter,

        #[Target('auth_resend_verification')]
        private RateLimiterFactoryInterface $resendLimiter,

        #[Target('auth_token_completion')]
        private RateLimiterFactoryInterface $tokenCompletionLimiter,

        private RequestStack $requestStack,
    ) {
    }

    public function consumeRegistrationAttempt(): void
    {
        $this->consume($this->registrationLimiter, 'registration');
    }

    public function consumeResendAttempt(): void
    {
        $this->consume($this->resendLimiter, 'resend-verification');
    }

    public function consumeTokenCompletionAttempt(): void
    {
        $this->consume($this->tokenCompletionLimiter, 'token-completion');
    }

    private function consume(RateLimiterFactory $factory, string $purpose): void
    {
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'unknown';
        $limit = $factory->create($purpose.':'.$ip)->consume(1);

        if ($limit->isAccepted()) {
            return;
        }

        $retryAfter = $limit->getRetryAfter();
        $retrySeconds = null === $retryAfter ? 60 : max(1, $retryAfter->getTimestamp() - time());

        throw new TooManyRequestsHttpException($retrySeconds, 'Too many authentication requests. Try again later.');
    }
}
