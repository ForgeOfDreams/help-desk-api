<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\NotificationChannel;
use App\Enum\NotificationDeliveryStatus;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\NotificationDeliveryRepository::class)]
#[ORM\Table(
    name: 'notification_deliveries',
    indexes: [new ORM\Index(name: 'idx_notification_deliveries_status_created', columns: ['status', 'created_at'])]
)]
#[ORM\HasLifecycleCallbacks]
class NotificationDelivery extends AbstractTimestampedEntity
{
    #[ORM\ManyToOne(targetEntity: Notification::class, inversedBy: 'deliveries')]
    #[ORM\JoinColumn(name: 'notification_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Notification $notification = null;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: NotificationChannel::class)]
    private NotificationChannel $channel = NotificationChannel::InApp;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: NotificationDeliveryStatus::class, options: ['default' => 'pending'])]
    private NotificationDeliveryStatus $status = NotificationDeliveryStatus::Pending;

    #[ORM\Column(type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(name: 'last_error', type: Types::TEXT, nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column(name: 'sent_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $sentAt = null;

    public function getNotification(): ?Notification
    {
        return $this->notification;
    }

    public function setNotification(?Notification $notification): self
    {
        if ($this->notification === $notification) {
            return $this;
        }

        $previous = $this->notification;
        $this->notification = $notification;
        $previous?->getDeliveries()->removeElement($this);
        if ($notification !== null && !$notification->getDeliveries()->contains($this)) {
            $notification->getDeliveries()->add($this);
        }

        return $this;
    }

    public function getChannel(): NotificationChannel
    {
        return $this->channel;
    }

    public function setChannel(NotificationChannel $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getStatus(): NotificationDeliveryStatus
    {
        return $this->status;
    }

    public function setStatus(NotificationDeliveryStatus $status): self
    {
        $this->status = $status;
        if ($status === NotificationDeliveryStatus::Sent) {
            $this->sentAt ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $this->lastError = null;
        } else {
            $this->sentAt = null;
        }

        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function incrementAttempts(): self
    {
        ++$this->attempts;

        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): self
    {
        $this->lastError = $lastError;

        return $this;
    }

    public function getSentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }
}
