<?php

declare(strict_types=1);

namespace App\Enum;

enum TeamMemberRole: string
{
    case Member = 'member';
    case Lead = 'lead';
}
