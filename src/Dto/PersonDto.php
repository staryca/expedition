<?php

declare(strict_types=1);

namespace App\Dto;

class PersonDto extends PlaceDto
{
    public string $name = '';
    public bool $isUnknown = false;
    public ?int $birth = null;
    public bool $isOrganization = false;
    public bool $isStudent = false;
}
