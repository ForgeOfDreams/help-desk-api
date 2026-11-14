<?php

declare(strict_types=1);

namespace App\Enum;

enum TicketStatus: string
{
    case New = 'new';
    case Open = 'open';
    case InProgress = 'in_progress';
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function isTerminal(): bool
    {
        return $this === self::Closed;
    }
}
