<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateManagedUserInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 254)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 150)]
    public string $displayName = '';

    /** Public admin endpoint deliberately cannot grant ROLE_ADMIN. */
    #[Assert\Choice(choices: ['ROLE_USER', 'ROLE_AGENT'])]
    public string $role = 'ROLE_AGENT';
}
