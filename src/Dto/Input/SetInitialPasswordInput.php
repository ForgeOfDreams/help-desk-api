<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class SetInitialPasswordInput
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-f0-9]{64}$/D')]
    public string $token = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 12, max: 1024)]
    public string $newPassword = '';
}
