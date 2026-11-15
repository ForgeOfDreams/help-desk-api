<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TicketHistoryRepository::class)]
#[ORM\Table(
    name: 'ticket_history',
    indexes: [new ORM\Index(name: 'idx_ticket_history_ticket_created', columns: ['ticket_id', 'created_at'])]
)]
#[ORM\HasLifecycleCallbacks]
class TicketHistory extends AbstractCreatedAtEntity
{
    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\Column(length: 100)]
    private string $action = '';

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'changes_json', type: Types::JSON)]
    private array $changes = [];

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
        $previous?->getHistory()->removeElement($this);
        if (null !== $ticket && !$ticket->getHistory()->contains($this)) {
            $ticket->getHistory()->add($this);
        }

        return $this;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): self
    {
        $this->actor = $actor;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $action = trim($action);
        if ('' === $action) {
            throw new \InvalidArgumentException('History action cannot be empty.');
        }
        $this->action = $action;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /** @param array<string, mixed> $changes */
    public function setChanges(array $changes): self
    {
        $this->changes = $changes;

        return $this;
    }
}
