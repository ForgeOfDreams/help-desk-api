<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[ORM\Table(name: 'users', indexes: [new ORM\Index(name: 'idx_users_active_deleted', columns: ['is_active', 'deleted_at'])])]
#[ORM\HasLifecycleCallbacks]
class User extends AbstractTimestampedEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(length: 254, unique: true)]
    private string $email = '';

    #[ORM\Column(name: 'password_hash', length: 255)]
    private string $passwordHash = '';

    #[ORM\Column(name: 'display_name', length: 150)]
    private string $displayName = '';

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'email_verified_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    /** @var Collection<int, Role> */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Collection $roles;

    /** @var Collection<int, TeamMember> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: TeamMember::class)]
    private Collection $teamMemberships;

    /** @var Collection<int, Ticket> */
    #[ORM\OneToMany(mappedBy: 'requester', targetEntity: Ticket::class)]
    private Collection $requestedTickets;

    /** @var Collection<int, Ticket> */
    #[ORM\OneToMany(mappedBy: 'assignee', targetEntity: Ticket::class)]
    private Collection $assignedTickets;

    /** @var Collection<int, TicketComment> */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: TicketComment::class)]
    private Collection $comments;

    /** @var Collection<int, TicketAttachment> */
    #[ORM\OneToMany(mappedBy: 'uploadedBy', targetEntity: TicketAttachment::class)]
    private Collection $uploadedAttachments;

    /** @var Collection<int, Notification> */
    #[ORM\OneToMany(mappedBy: 'recipient', targetEntity: Notification::class)]
    private Collection $notifications;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->teamMemberships = new ArrayCollection();
        $this->requestedTickets = new ArrayCollection();
        $this->assignedTickets = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->uploadedAttachments = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $email = trim($email);
        $this->email = strtolower($email);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roleCodes = [];
        foreach ($this->roles as $role) {
            $roleCodes[] = $role->getCode();
        }

        $roleCodes[] = 'ROLE_USER';

        return array_values(array_unique($roleCodes));
    }

    /** @return Collection<int, Role> */
    public function getRoleEntities(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addUser($this);
        }

        return $this;
    }

    public function removeRole(Role $role): self
    {
        if ($this->roles->removeElement($role)) {
            $role->removeUser($this);
        }

        return $this;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPassword(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Intentionally empty: plaintext credentials are never stored on this entity.
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = trim($displayName);

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

    public function getEmailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?DateTimeImmutable $emailVerifiedAt): self
    {
        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function deactivateSoftly(?DateTimeImmutable $at = null): self
    {
        $this->isActive = false;
        $this->deletedAt = $at ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $this;
    }

    public function restore(): self
    {
        $this->isActive = true;
        $this->deletedAt = null;

        return $this;
    }

    /** @return Collection<int, TeamMember> */
    public function getTeamMemberships(): Collection
    {
        return $this->teamMemberships;
    }

    /** @return Collection<int, Ticket> */
    public function getRequestedTickets(): Collection
    {
        return $this->requestedTickets;
    }

    /** @return Collection<int, Ticket> */
    public function getAssignedTickets(): Collection
    {
        return $this->assignedTickets;
    }

    /** @return Collection<int, TicketComment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /** @return Collection<int, TicketAttachment> */
    public function getUploadedAttachments(): Collection
    {
        return $this->uploadedAttachments;
    }

    /** @return Collection<int, Notification> */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }
}
