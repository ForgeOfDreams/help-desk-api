<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserActionToken;
use App\Enum\UserActionTokenPurpose;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<UserActionToken> */
final class UserActionTokenRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserActionToken::class);
    }

    /**
     * Must be called inside an active transaction. The row lock prevents two
     * concurrent requests from consuming the same single-use token.
     */
    public function findForUpdateByHash(string $hash): ?UserActionToken
    {
        return $this->createQueryBuilder('token')
            ->andWhere('token.tokenHash = :hash')
            ->setParameter('hash', $hash)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    /** @return list<UserActionToken> */
    public function findUnconsumedForUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'consumedAt' => null]);
    }

    public function hasPurposeForUser(User $user, UserActionTokenPurpose $purpose): bool
    {
        return (int) $this->createQueryBuilder('token')
            ->select('COUNT(token.id)')
            ->andWhere('token.user = :user')
            ->andWhere('token.purpose = :purpose')
            ->setParameter('user', $user)
            ->setParameter('purpose', $purpose->value)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
