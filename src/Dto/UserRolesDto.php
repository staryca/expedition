<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\User;

class UserRolesDto
{
    public ?User $user = null;
    /** @var array<string> $roles */
    public array $roles = [];

    public function getHash(): string
    {
        $hash = $this->user?->getHash();

        foreach ($this->roles as $role) {
            $hash = $hash . $role;
        }

        return $hash;
    }
}
