<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TeamRepository::class)]
#[ORM\Table(name: 'teams', indexes: [new ORM\Index(name: 'idx_teams_active_name', columns: ['is_active', 'name'])])]
#[ORM\HasLifecycleCallbacks]
class Team extends AbstractTimestampedEntity
{
    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(length: 64, unique: true)]
    private string $code = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    /** @var Collection<int, TeamMember> */
    #[ORM\OneToMany(mappedBy: 'team', targetEntity: TeamMember::class)]
    private Collection $memberships;

    /** @var Collection<int, Ticket> */
    #[ORM\OneToMany(mappedBy: 'team', targetEntity: Ticket::class)]
    private Collection $tickets;

    public function __construct()
    {
        $this->memberships = new ArrayCollection();
        $this->tickets = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtolower(trim($code));

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = null === $description ? null : trim($description);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /** @return Collection<int, TeamMember> */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function addMembership(TeamMember $membership): self
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setTeam($this);
        }

        return $this;
    }

    public function removeMembership(TeamMember $membership): self
    {
        if ($this->memberships->removeElement($membership) && $membership->getTeam() === $this) {
            $membership->setTeam(null);
        }

        return $this;
    }

    /** @return Collection<int, Ticket> */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }
}
