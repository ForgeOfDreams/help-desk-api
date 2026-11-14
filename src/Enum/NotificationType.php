<?php

declare(strict_types=1);

namespace App\Enum;

enum NotificationType: string
{
    case TicketCreated = 'ticket_created';
    case TicketAssigned = 'ticket_assigned';
    case TicketStatusChanged = 'ticket_status_changed';
    case CommentCreated = 'comment_created';
    case Mentioned = 'mentioned';
}
