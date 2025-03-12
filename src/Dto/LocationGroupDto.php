<?php

declare(strict_types=1);

namespace App\Dto;

class LocationGroupDto
{
    public string $id;
    public string $name = '';

    /** @var array<LocationGroupDto> $items */
    public array $items = [];

    /** @var array<string, LocationGroupDto> $groups */
    public array $groups = [];

    public function addItem(LocationGroupDto $item): void
    {
        $this->items[] = $item;
    }

    public function addGroup(string $group, LocationGroupDto $item): void
    {
        $this->groups[$group] = $item;
    }

    public function getOrCreateGroup(string $group): LocationGroupDto
    {
        if (!isset($this->groups[$group])) {
            $this->groups[$group] = new LocationGroupDto();
            $this->groups[$group]->name = $group;
        }

        return $this->groups[$group];
    }
}
