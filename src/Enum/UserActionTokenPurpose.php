<?php

declare(strict_types=1);

namespace App\Enum;

enum UserActionTokenPurpose: string
{
    case VerifyEmail = 'verify_email';
    case SetPassword = 'set_password';
}
