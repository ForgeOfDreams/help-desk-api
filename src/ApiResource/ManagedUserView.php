<?php

declare(strict_types=1);

namespace App\ApiResource;

use App\Entity\User;

final readonly class ManagedUserView
{
    /** @param list<string> $roles */
    public function __construct(
        public string $id,
        public string $email,
        public string $displayName,
        public array $roles,
        public bool $isActive,
        public ?\DateTimeImmutable $emailVerifiedAt,
        public ?\DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->getId()?->toRfc4122() ?? throw new \LogicException('A persisted user must have an ID.'),
            email: $user->getEmail(),
            displayName: $user->getDisplayName(),
            roles: $user->getRoles(),
            isActive: $user->isActive(),
            emailVerifiedAt: $user->getEmailVerifiedAt(),
            createdAt: $user->getCreatedAt(),
        );
    }
}
