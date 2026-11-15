<?php

declare(strict_types=1);

namespace App\Enum;

enum CommentVisibility: string
{
    case Public = 'public';
    case Internal = 'internal';
}
