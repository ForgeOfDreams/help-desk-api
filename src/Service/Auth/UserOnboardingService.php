<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserActionToken;
use App\Enum\UserActionTokenPurpose;
use App\Repository\RoleRepository;
use App\Repository\UserActionTokenRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserOnboardingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $users,
        private RoleRepository $roles,
        private UserActionTokenRepository $tokens,
        private UserPasswordHasherInterface $passwordHasher,
        private UserActionMailer $mailer,
        private LoggerInterface $logger,
    ) {
    }

    /** Public registration. The same API response is used for existing and new addresses. */
    public function register(string $email, string $displayName, string $plainPassword): void
    {
        $email = self::normalizeEmail($email);

        if ($this->users->findOneBy(['email' => $email]) instanceof User) {
            return;
        }

        try {
            /** @var array{0: User|null, 1: string|null} $created */
            $created = $this->entityManager->wrapInTransaction(function () use ($email, $displayName, $plainPassword): array {
                // Re-check within the transaction; the DB unique constraint remains the final guard.
                if ($this->users->findOneBy(['email' => $email]) instanceof User) {
                    return [null, null];
                }

                $user = (new User())
                    ->setEmail($email)
                    ->setDisplayName($displayName)
                    ->setPassword('pending')
                    ->setIsActive(false)
                    ->setEmailVerifiedAt(null);
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

                $this->entityManager->persist($user);
                $rawToken = $this->issueToken($user, UserActionTokenPurpose::VerifyEmail, 24, invalidateExisting: false);
                $this->entityManager->flush();

                return [$user, $rawToken];
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent registration may have won the unique email constraint.
            // Do not reveal this to an unauthenticated caller.
            $this->logger->info('Concurrent duplicate public registration was rejected by the database.');

            return;
        }

        [$user, $rawToken] = $created;
        if ($user instanceof User && is_string($rawToken)) {
            $this->mailer->sendEmailVerification($user, $rawToken);
        }
    }

    /** Creates an inactive account and emails a one-time password setup invitation. */
    public function invite(string $email, string $displayName, string $roleCode): User
    {
        $email = self::normalizeEmail($email);
        $roleCode = strtoupper(trim($roleCode));

        if (!in_array($roleCode, ['ROLE_USER', 'ROLE_AGENT'], true)) {
            throw new \InvalidArgumentException('Only ROLE_USER and ROLE_AGENT can be assigned through the admin API.');
        }

        if ($this->users->findOneBy(['email' => $email]) instanceof User) {
            throw new ConflictHttpException('A user with this email already exists.');
        }

        try {
            /** @var array{0: User|null, 1: string|null} $created */
            $created = $this->entityManager->wrapInTransaction(function () use ($email, $displayName, $roleCode): array {
                if ($this->users->findOneBy(['email' => $email]) instanceof User) {
                    return [null, null];
                }

                $temporarySecret = bin2hex(random_bytes(32));
                $user = (new User())
                    ->setEmail($email)
                    ->setDisplayName($displayName)
                    ->setPassword('pending');
                $user->setPassword($this->passwordHasher->hashPassword($user, $temporarySecret));
                $user->setIsActive(false)->setEmailVerifiedAt(null);

                if ('ROLE_AGENT' === $roleCode) {
                    $role = $this->findOrCreateRole('ROLE_AGENT', 'Support agent');
                    $user->addRole($role);
                }

                $this->entityManager->persist($user);
                $rawToken = $this->issueToken($user, UserActionTokenPurpose::SetPassword, 48, invalidateExisting: false);
                $this->entityManager->flush();

                return [$user, $rawToken];
            });
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('A user with this email already exists.');
        }

        [$user, $rawToken] = $created;
        if (!$user instanceof User || !is_string($rawToken)) {
            throw new ConflictHttpException('A user with this email already exists.');
        }

        $this->mailer->sendSetPassword($user, $rawToken);

        return $user;
    }

    public function resendInstructions(string $email): void
    {
        $email = self::normalizeEmail($email);
        $user = $this->users->findOneBy(['email' => $email]);

        if (!$user instanceof User || $user->isActive() || null !== $user->getEmailVerifiedAt() || null !== $user->getDeletedAt()) {
            return;
        }

        $purpose = $this->tokens->hasPurposeForUser($user, UserActionTokenPurpose::SetPassword)
            ? UserActionTokenPurpose::SetPassword
            : UserActionTokenPurpose::VerifyEmail;

        $token = $this->entityManager->wrapInTransaction(function () use ($user, $purpose): string {
            $rawToken = $this->issueToken($user, $purpose, UserActionTokenPurpose::SetPassword === $purpose ? 48 : 24);
            $this->entityManager->flush();

            return $rawToken;
        });

        if (UserActionTokenPurpose::SetPassword === $purpose) {
            $this->mailer->sendSetPassword($user, $token);
        } else {
            $this->mailer->sendEmailVerification($user, $token);
        }
    }

    public function verifyEmail(string $rawToken): void
    {
        $verified = $this->entityManager->wrapInTransaction(function () use ($rawToken): bool {
            $token = $this->tokens->findForUpdateByHash(hash('sha256', $rawToken));
            $now = self::utcNow();

            if (!$this->isUsable($token, UserActionTokenPurpose::VerifyEmail, $now)) {
                return false;
            }

            $user = $token->getUser();
            if (null !== $user->getEmailVerifiedAt() || null !== $user->getDeletedAt()) {
                return false;
            }

            $token->consume($now);
            $user->setEmailVerifiedAt($now)->setIsActive(true);
            $this->entityManager->flush();

            return true;
        });

        if (!$verified) {
            throw new BadRequestHttpException('The action token is invalid, expired or already used.');
        }
    }

    public function setInitialPassword(string $rawToken, string $plainPassword): void
    {
        $completed = $this->entityManager->wrapInTransaction(function () use ($rawToken, $plainPassword): bool {
            $token = $this->tokens->findForUpdateByHash(hash('sha256', $rawToken));
            $now = self::utcNow();

            if (!$this->isUsable($token, UserActionTokenPurpose::SetPassword, $now)) {
                return false;
            }

            $user = $token->getUser();
            if ($user->isActive() || null !== $user->getEmailVerifiedAt() || null !== $user->getDeletedAt()) {
                return false;
            }

            $token->consume($now);
            $user
                ->setPassword($this->passwordHasher->hashPassword($user, $plainPassword))
                ->setEmailVerifiedAt($now)
                ->setIsActive(true);

            $this->entityManager->flush();

            return true;
        });

        if (!$completed) {
            throw new BadRequestHttpException('The action token is invalid, expired or already used.');
        }
    }

    private function isUsable(?UserActionToken $token, UserActionTokenPurpose $purpose, \DateTimeImmutable $now): bool
    {
        return null !== $token
            && $token->getPurpose() === $purpose
            && !$token->isConsumed()
            && !$token->isExpiredAt($now);
    }

    private function issueToken(
        User $user,
        UserActionTokenPurpose $purpose,
        int $ttlHours,
        bool $invalidateExisting = true,
    ): string {
        $now = self::utcNow();
        if ($invalidateExisting) {
            foreach ($this->tokens->findUnconsumedForUser($user) as $existingToken) {
                if ($existingToken->getPurpose() === $purpose) {
                    $existingToken->consume($now);
                }
            }
        }

        $rawToken = bin2hex(random_bytes(32));
        $token = (new UserActionToken())
            ->setUser($user)
            ->setPurpose($purpose)
            ->setTokenHash(hash('sha256', $rawToken))
            ->setExpiresAt($now->modify(sprintf('+%d hours', $ttlHours)));

        $this->entityManager->persist($token);

        return $rawToken;
    }

    private function findOrCreateRole(string $code, string $name): Role
    {
        $role = $this->roles->findByCode($code);
        if ($role instanceof Role) {
            return $role;
        }

        $role = (new Role())->setCode($code)->setName($name);
        $this->entityManager->persist($role);

        return $role;
    }

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private static function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
