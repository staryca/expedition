<?php

declare(strict_types=1);

namespace App\Dto;

class PersonBsuDto extends PersonDto
{
    public string $codeReport = '';

    public function make(PersonDto $dto): self
    {
        $this->name = trim($dto->name);
        $this->birth = $dto->birth;
        $this->isOrganization = $dto->isOrganization;
        $this->isStudent = $dto->isStudent;
        $this->isUnknown = $dto->isUnknown;

        return $this;
    }
}
