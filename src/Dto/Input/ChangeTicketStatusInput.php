<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangeTicketStatusInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['new', 'open', 'in_progress', 'pending', 'resolved', 'closed'])]
    public string $status = '';

    #[Assert\Length(max: 2000)]
    public ?string $reason = null;
}
