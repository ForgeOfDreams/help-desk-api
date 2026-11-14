<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TicketNumberCounter;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/** @extends AbstractRepository<TicketNumberCounter> */
final class TicketNumberCounterRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketNumberCounter::class);
    }

    /**
     * Atomically increments a named counter using MySQL's connection-scoped LAST_INSERT_ID().
     * The upsert is safe when multiple requests create tickets concurrently.
     *
     * Returns the new number as a string because Doctrine maps BIGINT values to strings for portability.
     */
    public function nextValue(string $name = 'ticket'): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Counter name cannot be empty.');
        }

        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement(
            <<<'SQL'
                INSERT INTO ticket_number_counters (counter_name, counter_value)
                VALUES (:name, LAST_INSERT_ID(1))
                ON DUPLICATE KEY UPDATE counter_value = LAST_INSERT_ID(counter_value + 1)
                SQL,
            ['name' => $name],
            ['name' => Types::STRING],
        );

        $value = $connection->fetchOne('SELECT LAST_INSERT_ID()');
        if (!is_numeric($value) || (string) $value === '0') {
            throw new RuntimeException('Could not allocate the next ticket number.');
        }

        return (string) $value;
    }
}
