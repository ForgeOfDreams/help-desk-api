<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/** @extends AbstractRepository<TeamMember> */
final class TeamMemberRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamMember::class);
    }

    public function findMembership(Team $team, User $user): ?TeamMember
    {
        return $this->findOneBy(['team' => $team, 'user' => $user]);
    }

    public function isMemberOfTeam(Team $team, User $user): bool
    {
        return $this->count(['team' => $team, 'user' => $user]) > 0;
    }

    /** @return list<TeamMember> */
    public function findForTeam(Team $team): array
    {
        return $this->createQueryBuilder('tm')
            ->addSelect('u')
            ->innerJoin('tm.user', 'u')
            ->andWhere('tm.team = :team')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('team', $team)
            ->orderBy('u.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<TeamMember> */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('tm')
            ->addSelect('t')
            ->innerJoin('tm.team', 't')
            ->andWhere('tm.user = :user')
            ->andWhere('t.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
