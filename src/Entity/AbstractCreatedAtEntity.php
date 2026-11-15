<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractCreatedAtEntity extends AbstractUuidEntity
{
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        $this->createdAt ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
