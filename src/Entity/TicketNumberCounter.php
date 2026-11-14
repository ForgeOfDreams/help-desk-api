<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TicketNumberCounterRepository::class)]
#[ORM\Table(name: 'ticket_number_counters')]
class TicketNumberCounter
{
    #[ORM\Id]
    #[ORM\Column(name: 'counter_name', length: 64)]
    private string $counterName = 'ticket';

    #[ORM\Column(name: 'counter_value', type: Types::BIGINT, options: ['unsigned' => true])]
    private string $counterValue = '0';

    public function getCounterName(): string
    {
        return $this->counterName;
    }

    public function setCounterName(string $counterName): self
    {
        $counterName = trim($counterName);
        if ($counterName === '') {
            throw new \InvalidArgumentException('Counter name cannot be empty.');
        }
        $this->counterName = $counterName;

        return $this;
    }

    public function getCounterValue(): string
    {
        return $this->counterValue;
    }

    public function setCounterValue(string|int $counterValue): self
    {
        $value = (string) $counterValue;
        if (!ctype_digit($value)) {
            throw new \InvalidArgumentException('Counter value must be a non-negative integer.');
        }
        $this->counterValue = $value;

        return $this;
    }
}
