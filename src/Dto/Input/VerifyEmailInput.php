<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class VerifyEmailInput
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-f0-9]{64}$/D')]
    public string $token = '';
}
