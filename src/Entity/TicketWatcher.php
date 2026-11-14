<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TicketWatcherRepository::class)]
#[ORM\Table(
    name: 'ticket_watchers',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_ticket_watcher', columns: ['ticket_id', 'user_id'])],
    indexes: [new ORM\Index(name: 'idx_ticket_watchers_user', columns: ['user_id', 'ticket_id'])]
)]
#[ORM\HasLifecycleCallbacks]
class TicketWatcher extends AbstractCreatedAtEntity
{
    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'watchers')]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

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
        $previous?->getWatchers()->removeElement($this);
        if ($ticket !== null && !$ticket->getWatchers()->contains($this)) {
            $ticket->getWatchers()->add($this);
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
