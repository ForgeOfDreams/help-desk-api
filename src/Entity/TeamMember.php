<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TeamMemberRole;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TeamMemberRepository::class)]
#[ORM\Table(
    name: 'team_members',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_team_member', columns: ['team_id', 'user_id'])],
    indexes: [new ORM\Index(name: 'idx_team_members_user', columns: ['user_id'])]
)]
#[ORM\HasLifecycleCallbacks]
class TeamMember extends AbstractTimestampedEntity
{
    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?Team $team = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'teamMemberships')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?User $user = null;

    #[ORM\Column(name: 'role_in_team', type: Types::STRING, length: 16, enumType: TeamMemberRole::class, options: ['default' => 'member'])]
    private TeamMemberRole $roleInTeam = TeamMemberRole::Member;

    #[ORM\Column(name: 'joined_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $joinedAt = null;

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        if ($this->team === $team) {
            return $this;
        }

        $previous = $this->team;
        $this->team = $team;
        $previous?->getMemberships()->removeElement($this);
        if (null !== $team && !$team->getMemberships()->contains($this)) {
            $team->getMemberships()->add($this);
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        if ($this->user === $user) {
            return $this;
        }

        $previous = $this->user;
        $this->user = $user;
        $previous?->getTeamMemberships()->removeElement($this);
        if (null !== $user && !$user->getTeamMemberships()->contains($this)) {
            $user->getTeamMemberships()->add($this);
        }

        return $this;
    }

    public function getRoleInTeam(): TeamMemberRole
    {
        return $this->roleInTeam;
    }

    public function setRoleInTeam(TeamMemberRole $roleInTeam): self
    {
        $this->roleInTeam = $roleInTeam;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    #[ORM\PrePersist]
    public function initializeJoinedAt(): void
    {
        $this->joinedAt ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
