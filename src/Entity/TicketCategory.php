<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: \App\Repository\TicketCategoryRepository::class)]
#[ORM\Table(
    name: 'ticket_categories',
    indexes: [new ORM\Index(name: 'idx_ticket_categories_parent_active', columns: ['parent_id', 'is_active'])]
)]
#[ORM\HasLifecycleCallbacks]
class TicketCategory extends AbstractTimestampedEntity
{
    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(length: 64, unique: true)]
    private string $code = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    /** @var Collection<int, TicketCategory> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $children;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    /** @var Collection<int, Ticket> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Ticket::class)]
    private Collection $tickets;

    public function __construct()
    {
        $this->children = new ArrayCollection();
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
        $this->description = $description === null ? null : trim($description);

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        if ($parent === $this) {
            throw new InvalidArgumentException('A ticket category cannot be its own parent.');
        }
        if ($this->parent === $parent) {
            return $this;
        }

        $previous = $this->parent;
        $this->parent = $parent;
        $previous?->getChildren()->removeElement($this);
        if ($parent !== null && !$parent->getChildren()->contains($this)) {
            $parent->getChildren()->add($this);
        }

        return $this;
    }

    /** @return Collection<int, TicketCategory> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }

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

    /** @return Collection<int, Ticket> */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }
}
