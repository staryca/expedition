<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Type\GenderType;

class NameGenderDto
{
    private string $name;
    public int $gender = GenderType::UNKNOWN;

    public function __construct(string $name, int $gender = GenderType::UNKNOWN)
    {
        $this->name = $name;
        $this->gender = $gender;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
