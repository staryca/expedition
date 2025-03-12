<?php

declare(strict_types=1);

namespace App\Dto;

class UserDto
{
    public string $name = '';
    /** @var array<string> $roles */
    public array $roles = [];
}
