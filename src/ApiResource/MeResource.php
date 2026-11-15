<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\MeProvider;

#[ApiResource(
    shortName: 'CurrentUser',
    operations: [
        new Get(
            uriTemplate: '/me',
            uriVariables: [],
            provider: MeProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
)]
final class MeResource
{
    #[ApiProperty(identifier: true)]
    public ?string $id = null;
    public string $email = '';
    public string $displayName = '';
    /** @var list<string> */
    public array $roles = [];
    public ?\DateTimeImmutable $createdAt = null;
}
