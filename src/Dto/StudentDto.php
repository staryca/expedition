<?php

declare(strict_types=1);

namespace App\Dto;

class StudentDto extends PlaceDto
{
    public string $name;

    /**
     * String locations for BSU reports
     * @var array<string> $locations
     */
    public array $locations = [];

    public function addLocation(?string $location): void
    {
        if (null === $location) {
            return;
        }

        if (!in_array($location, $this->locations, true)) {
            $this->locations[] = $location;
        }
    }

    /**
     * @param array<string> $locations
     * @return void
     */
    public function addLocations(array $locations): void
    {
        foreach ($locations as $location) {
            $this->addLocation($location);
        }
    }

    public function isSame(StudentDto $dto): bool
    {
        if (!$this->isSameName($dto->name)) {
            return false;
        }

        return $this->isSameLocation($dto->locations);
    }

    public function isSameName(string $name): bool
    {
        return $name === $this->name;
    }

    public function isSameLocation(array $locations): bool
    {
        foreach ($locations as $location) {
            if (in_array($location, $this->locations, true)) {
                return true;
            }
        }

        return false;
    }
}
