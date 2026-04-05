<?php

declare(strict_types=1);

namespace App\Dto;

class UserDto
{
    public string $name = '';
    /** @var array<string> $roles */
    public array $roles = [];
    public ?bool $found = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getHash(): string
    {
        $hash = $this->name;

        foreach ($this->roles as $role) {
            $hash = $hash . $role;
        }

        return $hash;
    }
}
