<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangeOwnPasswordInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 1024)]
    public string $currentPassword = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 12, max: 1024)]
    public string $newPassword = '';
}
