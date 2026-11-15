<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\CommentVisibility;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TicketCommentRepository::class)]
#[ORM\Table(
    name: 'ticket_comments',
    indexes: [new ORM\Index(name: 'idx_ticket_comments_ticket_created', columns: ['ticket_id', 'created_at'])]
)]
#[ORM\HasLifecycleCallbacks]
class TicketComment extends AbstractTimestampedEntity
{
    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?User $author = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $body = '';

    #[ORM\Column(type: Types::STRING, length: 16, enumType: CommentVisibility::class, options: ['default' => 'public'])]
    private CommentVisibility $visibility = CommentVisibility::Public;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /** @var Collection<int, TicketAttachment> */
    #[ORM\OneToMany(mappedBy: 'comment', targetEntity: TicketAttachment::class)]
    private Collection $attachments;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

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
        $previous?->getComments()->removeElement($this);
        if (null !== $ticket && !$ticket->getComments()->contains($this)) {
            $ticket->getComments()->add($this);
        }

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        if ($this->author === $author) {
            return $this;
        }

        $previous = $this->author;
        $this->author = $author;
        $previous?->getComments()->removeElement($this);
        if (null !== $author && !$author->getComments()->contains($this)) {
            $author->getComments()->add($this);
        }

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $body = trim($body);
        if ('' === $body) {
            throw new \InvalidArgumentException('Comment body cannot be empty.');
        }
        $this->body = $body;

        return $this;
    }

    public function getVisibility(): CommentVisibility
    {
        return $this->visibility;
    }

    public function setVisibility(CommentVisibility $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(?\DateTimeImmutable $at = null): self
    {
        $this->deletedAt = $at ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return $this;
    }

    /** @return Collection<int, TicketAttachment> */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }
}
