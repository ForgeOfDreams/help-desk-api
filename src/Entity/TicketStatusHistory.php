<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TicketStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TicketStatusHistoryRepository::class)]
#[ORM\Table(
    name: 'ticket_status_history',
    indexes: [new ORM\Index(name: 'idx_ticket_status_history_ticket_created', columns: ['ticket_id', 'created_at'])]
)]
#[ORM\HasLifecycleCallbacks]
class TicketStatusHistory extends AbstractCreatedAtEntity
{
    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'changed_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $changedBy = null;

    #[ORM\Column(name: 'old_status', type: Types::STRING, length: 32, enumType: TicketStatus::class, nullable: true)]
    private ?TicketStatus $oldStatus = null;

    #[ORM\Column(name: 'new_status', type: Types::STRING, length: 32, enumType: TicketStatus::class)]
    private TicketStatus $newStatus = TicketStatus::New;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): self
    {
        if ($this->ticket === $ticket) {
            return $this;
        }

        $previous = $this->ticket;
        $this->ticket = $ticket;
        $previous?->getStatusHistory()->removeElement($this);
        if ($ticket !== null && !$ticket->getStatusHistory()->contains($this)) {
            $ticket->getStatusHistory()->add($this);
        }

        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): self
    {
        $this->changedBy = $changedBy;

        return $this;
    }

    public function getOldStatus(): ?TicketStatus
    {
        return $this->oldStatus;
    }

    public function setOldStatus(?TicketStatus $oldStatus): self
    {
        $this->oldStatus = $oldStatus;

        return $this;
    }

    public function getNewStatus(): TicketStatus
    {
        return $this->newStatus;
    }

    public function setNewStatus(TicketStatus $newStatus): self
    {
        $this->newStatus = $newStatus;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason === null ? null : trim($reason);

        return $this;
    }
}
