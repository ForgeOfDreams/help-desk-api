<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 254)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 150)]
    public string $displayName = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 12, max: 1024)]
    public string $password = '';
}
