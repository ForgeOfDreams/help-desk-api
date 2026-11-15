<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Enum\TicketStatus;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class TicketStatusTransitionPolicy
{
    /** @var array<string, list<string>> */
    private const ALLOWED_TRANSITIONS = [
        'new' => ['open', 'in_progress'],
        'open' => ['in_progress', 'pending', 'resolved'],
        'in_progress' => ['open', 'pending', 'resolved'],
        'pending' => ['open', 'in_progress', 'resolved'],
        'resolved' => ['open', 'closed'],
        'closed' => [],
    ];

    public function assertAllowed(TicketStatus $from, TicketStatus $to): void
    {
        if ($from === $to) {
            return;
        }

        if (!in_array($to->value, self::ALLOWED_TRANSITIONS[$from->value], true)) {
            throw new UnprocessableEntityHttpException(sprintf('Status transition from "%s" to "%s" is not allowed.', $from->value, $to->value));
        }
    }
}
