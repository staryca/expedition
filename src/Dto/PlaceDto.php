<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\GeoPoint;

class PlaceDto
{
    public ?GeoPoint $geoPoint = null;
    public ?string $place = null;

    public function getPlaceHash(): string
    {
        return '@' . $this->geoPoint?->getId() . '!!' . $this->place;
    }

    public function isEmptyPlace(): bool
    {
        return !$this->geoPoint && empty($this->place);
    }
}
