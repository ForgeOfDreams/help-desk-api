<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class AssignTicketInput
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $assigneeId = '';
}
