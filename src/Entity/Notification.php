<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\NotificationType;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\NotificationRepository::class)]
#[ORM\Table(
    name: 'notifications',
    indexes: [new ORM\Index(name: 'idx_notifications_user_read_created', columns: ['recipient_id', 'read_at', 'created_at'])]
)]
#[ORM\HasLifecycleCallbacks]
class Notification extends AbstractCreatedAtEntity
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(name: 'recipient_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $recipient = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Ticket $ticket = null;

    #[ORM\Column(type: Types::STRING, length: 32, enumType: NotificationType::class)]
    private NotificationType $type = NotificationType::TicketCreated;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $payload = [];

    #[ORM\Column(name: 'read_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $readAt = null;

    /** @var Collection<int, NotificationDelivery> */
    #[ORM\OneToMany(mappedBy: 'notification', targetEntity: NotificationDelivery::class)]
    private Collection $deliveries;

    public function __construct()
    {
        $this->deliveries = new ArrayCollection();
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): self
    {
        if ($this->recipient === $recipient) {
            return $this;
        }

        $previous = $this->recipient;
        $this->recipient = $recipient;
        $previous?->getNotifications()->removeElement($this);
        if ($recipient !== null && !$recipient->getNotifications()->contains($this)) {
            $recipient->getNotifications()->add($this);
        }

        return $this;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function setType(NotificationType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /** @param array<string, mixed> $payload */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    /** @return Collection<int, NotificationDelivery> */
    public function getDeliveries(): Collection
    {
        return $this->deliveries;
    }

    public function getReadAt(): ?DateTimeImmutable
    {
        return $this->readAt;
    }

    public function isRead(): bool
    {
        return $this->readAt !== null;
    }

    public function markAsRead(?DateTimeImmutable $at = null): self
    {
        $this->readAt ??= $at ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $this;
    }

    public function markAsUnread(): self
    {
        $this->readAt = null;

        return $this;
    }
}
