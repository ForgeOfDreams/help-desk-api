<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateTicketInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    public string $subject = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 20000)]
    public string $description = '';

    #[Assert\Choice(choices: ['low', 'normal', 'high', 'urgent'])]
    public string $priority = 'normal';

    #[Assert\Uuid]
    public ?string $categoryId = null;
}
