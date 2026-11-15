<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TicketAttachmentRepository::class)]
#[ORM\Table(
    name: 'ticket_attachments',
    indexes: [new ORM\Index(name: 'idx_ticket_attachments_ticket', columns: ['ticket_id'])]
)]
#[ORM\HasLifecycleCallbacks]
class TicketAttachment extends AbstractCreatedAtEntity
{
    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: TicketComment::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'comment_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?TicketComment $comment = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'uploadedAttachments')]
    #[ORM\JoinColumn(name: 'uploaded_by_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?User $uploadedBy = null;

    #[ORM\Column(name: 'storage_key', length: 512, unique: true)]
    private string $storageKey = '';

    #[ORM\Column(name: 'original_filename', length: 255)]
    private string $originalFilename = '';

    #[ORM\Column(name: 'mime_type', length: 127)]
    private string $mimeType = 'application/octet-stream';

    #[ORM\Column(name: 'size_bytes', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $sizeBytes = 0;

    #[ORM\Column(name: 'checksum_sha256', length: 64, nullable: true)]
    private ?string $checksumSha256 = null;

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
        $previous?->getAttachments()->removeElement($this);
        if (null !== $ticket && !$ticket->getAttachments()->contains($this)) {
            $ticket->getAttachments()->add($this);
        }

        return $this;
    }

    public function getComment(): ?TicketComment
    {
        return $this->comment;
    }

    public function setComment(?TicketComment $comment): self
    {
        if ($this->comment === $comment) {
            return $this;
        }

        $previous = $this->comment;
        $this->comment = $comment;
        $previous?->getAttachments()->removeElement($this);
        if (null !== $comment && !$comment->getAttachments()->contains($this)) {
            $comment->getAttachments()->add($this);
        }
        if (null !== $comment && null !== $comment->getTicket() && $this->ticket !== $comment->getTicket()) {
            $this->setTicket($comment->getTicket());
        }

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): self
    {
        if ($this->uploadedBy === $uploadedBy) {
            return $this;
        }

        $previous = $this->uploadedBy;
        $this->uploadedBy = $uploadedBy;
        $previous?->getUploadedAttachments()->removeElement($this);
        if (null !== $uploadedBy && !$uploadedBy->getUploadedAttachments()->contains($this)) {
            $uploadedBy->getUploadedAttachments()->add($this);
        }

        return $this;
    }

    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    public function setStorageKey(string $storageKey): self
    {
        $storageKey = trim($storageKey);
        if ('' === $storageKey) {
            throw new \InvalidArgumentException('Storage key cannot be empty.');
        }
        $this->storageKey = $storageKey;

        return $this;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): self
    {
        $this->originalFilename = trim($originalFilename);

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = trim($mimeType);

        return $this;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function setSizeBytes(int $sizeBytes): self
    {
        if ($sizeBytes < 0) {
            throw new \InvalidArgumentException('Attachment size cannot be negative.');
        }
        $this->sizeBytes = $sizeBytes;

        return $this;
    }

    public function getChecksumSha256(): ?string
    {
        return $this->checksumSha256;
    }

    public function setChecksumSha256(?string $checksumSha256): self
    {
        if (null !== $checksumSha256 && !preg_match('/^[a-f0-9]{64}$/i', $checksumSha256)) {
            throw new \InvalidArgumentException('SHA-256 checksum must contain 64 hexadecimal characters.');
        }
        $this->checksumSha256 = null === $checksumSha256 ? null : strtolower($checksumSha256);

        return $this;
    }
}
