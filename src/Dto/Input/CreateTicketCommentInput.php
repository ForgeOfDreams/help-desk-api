<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateTicketCommentInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 20000)]
    public string $body = '';

    #[Assert\Choice(choices: ['public', 'internal'])]
    public string $visibility = 'public';
}
