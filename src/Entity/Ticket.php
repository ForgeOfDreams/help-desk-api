<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TicketRepository::class)]
#[ORM\Table(
    name: 'tickets',
    indexes: [
        new ORM\Index(name: 'idx_tickets_requester_created', columns: ['requester_id', 'created_at']),
        new ORM\Index(name: 'idx_tickets_assignee_status', columns: ['assignee_id', 'status', 'updated_at']),
        new ORM\Index(name: 'idx_tickets_team_status', columns: ['team_id', 'status', 'updated_at']),
        new ORM\Index(name: 'idx_tickets_status_priority', columns: ['status', 'priority', 'created_at']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class Ticket extends AbstractTimestampedEntity
{
    #[ORM\Column(name: 'ticket_number', type: Types::BIGINT, unique: true, options: ['unsigned' => true])]
    private ?string $ticketNumber = null;

    #[ORM\Column(length: 200)]
    private string $subject = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(type: Types::STRING, length: 32, enumType: TicketStatus::class, options: ['default' => 'new'])]
    private TicketStatus $status = TicketStatus::New;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: TicketPriority::class, options: ['default' => 'normal'])]
    private TicketPriority $priority = TicketPriority::Normal;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'requestedTickets')]
    #[ORM\JoinColumn(name: 'requester_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?User $requester = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'assignedTickets')]
    #[ORM\JoinColumn(name: 'assignee_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $assignee = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Team $team = null;

    #[ORM\ManyToOne(targetEntity: TicketCategory::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?TicketCategory $category = null;

    #[ORM\Column(name: 'first_response_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $firstResponseAt = null;

    #[ORM\Column(name: 'resolved_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(name: 'closed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $version = 1;

    /** @var Collection<int, TicketComment> */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketComment::class)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $comments;

    /** @var Collection<int, TicketAttachment> */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketAttachment::class)]
    private Collection $attachments;

    /** @var Collection<int, TicketWatcher> */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketWatcher::class)]
    private Collection $watchers;

    /** @var Collection<int, TicketHistory> */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketHistory::class)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $history;

    /** @var Collection<int, TicketStatusHistory> */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketStatusHistory::class)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $statusHistory;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->watchers = new ArrayCollection();
        $this->history = new ArrayCollection();
        $this->statusHistory = new ArrayCollection();
    }

    public function getTicketNumber(): ?string
    {
        return $this->ticketNumber;
    }

    public function assignTicketNumber(string|int $ticketNumber): self
    {
        $number = (string) $ticketNumber;
        if (!ctype_digit($number) || '' === ltrim($number, '0')) {
            throw new \InvalidArgumentException('Ticket number must be a positive integer.');
        }
        $number = ltrim($number, '0');
        if (null !== $this->ticketNumber && $this->ticketNumber !== $number) {
            throw new \LogicException('A ticket number cannot be changed after assignment.');
        }

        $this->ticketNumber = $number;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $subject = trim($subject);
        if ('' === $subject) {
            throw new \InvalidArgumentException('Ticket subject cannot be empty.');
        }
        $this->subject = $subject;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);

        return $this;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): self
    {
        if ($this->status === $status) {
            return $this;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->status = $status;

        if (TicketStatus::Resolved === $status) {
            $this->resolvedAt ??= $now;
            $this->closedAt = null;
        } elseif (TicketStatus::Closed === $status) {
            $this->resolvedAt ??= $now;
            $this->closedAt = $now;
        } else {
            $this->resolvedAt = null;
            $this->closedAt = null;
        }

        return $this;
    }

    public function getPriority(): TicketPriority
    {
        return $this->priority;
    }

    public function setPriority(TicketPriority $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getRequester(): ?User
    {
        return $this->requester;
    }

    public function setRequester(?User $requester): self
    {
        if ($this->requester === $requester) {
            return $this;
        }

        $previous = $this->requester;
        $this->requester = $requester;
        $previous?->getRequestedTickets()->removeElement($this);
        if (null !== $requester && !$requester->getRequestedTickets()->contains($this)) {
            $requester->getRequestedTickets()->add($this);
        }

        return $this;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): self
    {
        if ($this->assignee === $assignee) {
            return $this;
        }

        $previous = $this->assignee;
        $this->assignee = $assignee;
        $previous?->getAssignedTickets()->removeElement($this);
        if (null !== $assignee && !$assignee->getAssignedTickets()->contains($this)) {
            $assignee->getAssignedTickets()->add($this);
        }

        return $this;
    }

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
        $previous?->getTickets()->removeElement($this);
        if (null !== $team && !$team->getTickets()->contains($this)) {
            $team->getTickets()->add($this);
        }

        return $this;
    }

    public function getCategory(): ?TicketCategory
    {
        return $this->category;
    }

    public function setCategory(?TicketCategory $category): self
    {
        if ($this->category === $category) {
            return $this;
        }

        $previous = $this->category;
        $this->category = $category;
        $previous?->getTickets()->removeElement($this);
        if (null !== $category && !$category->getTickets()->contains($this)) {
            $category->getTickets()->add($this);
        }

        return $this;
    }

    public function getFirstResponseAt(): ?\DateTimeImmutable
    {
        return $this->firstResponseAt;
    }

    public function markFirstResponse(?\DateTimeImmutable $at = null): self
    {
        $this->firstResponseAt ??= $at ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    /** @return Collection<int, TicketComment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /** @return Collection<int, TicketAttachment> */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    /** @return Collection<int, TicketWatcher> */
    public function getWatchers(): Collection
    {
        return $this->watchers;
    }

    /** @return Collection<int, TicketHistory> */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    /** @return Collection<int, TicketStatusHistory> */
    public function getStatusHistory(): Collection
    {
        return $this->statusHistory;
    }

    #[ORM\PrePersist]
    public function assertTicketNumberAssigned(): void
    {
        if (null === $this->ticketNumber) {
            throw new \LogicException('Assign a ticket number using TicketNumberCounterRepository before persisting the ticket.');
        }
        if (null === $this->requester) {
            throw new \LogicException('A ticket requester is required.');
        }
    }
}
