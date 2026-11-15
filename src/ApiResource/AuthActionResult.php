<?php

declare(strict_types=1);

namespace App\ApiResource;

/** Minimal response DTO; never returns token hashes, passwords or user internals. */
final readonly class AuthActionResult
{
    public function __construct(public string $message)
    {
    }
}
