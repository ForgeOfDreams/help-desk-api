<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserActionTokenPurpose;
use App\Repository\UserActionTokenRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserActionTokenRepository::class)]
#[ORM\Table(
    name: 'user_action_tokens',
    indexes: [
        new ORM\Index(name: 'idx_user_action_tokens_user', columns: ['user_id']),
        new ORM\Index(name: 'idx_user_action_tokens_expiry', columns: ['expires_at']),
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_user_action_tokens_hash', columns: ['token_hash']),
    ],
)]
#[ORM\HasLifecycleCallbacks]
class UserActionToken extends AbstractTimestampedEntity
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** SHA-256 hash of the raw, single-use token. The raw token is never persisted. */
    #[ORM\Column(name: 'token_hash', length: 64)]
    private string $tokenHash = '';

    #[ORM\Column(length: 32)]
    private string $purpose = '';

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'consumed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $consumedAt = null;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): self
    {
        if (strlen($tokenHash) !== 64 || !ctype_xdigit($tokenHash)) {
            throw new \InvalidArgumentException('Token hash must be a 64-character hexadecimal SHA-256 hash.');
        }

        $this->tokenHash = strtolower($tokenHash);

        return $this;
    }

    public function getPurpose(): UserActionTokenPurpose
    {
        return UserActionTokenPurpose::from($this->purpose);
    }

    public function setPurpose(UserActionTokenPurpose $purpose): self
    {
        $this->purpose = $purpose->value;

        return $this;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getConsumedAt(): ?DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function consume(DateTimeImmutable $at): self
    {
        $this->consumedAt ??= $at;

        return $this;
    }

    public function isExpiredAt(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function isConsumed(): bool
    {
        return $this->consumedAt !== null;
    }
}
