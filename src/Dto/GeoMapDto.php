<?php

declare(strict_types=1);

namespace App\Dto;

class GeoMapDto
{
    public const TYPE_REPORT = 1;
    public const TYPE_BASE = 2;
    public const TYPE_TIP = 3;
    public const TYPE_COMMENT = 4;
    public const TYPE_COMPLEX = 5;

    public int $zoom = 14;
    public ?LatLonDto $center = null;
    /** @var array<LatLonDto> $points */
    public array $points = [];
    /** @var array<string> $popups */
    public array $popups = [];
    /** @var array<int> $types */
    public array $types = [];

    public function addLatLon(LatLonDto $latLon, string $popup, int $type): void
    {
        if (null === $this->center) {
            $this->center = clone $latLon;
        } else {
            $count = count($this->points);
            $this->center->lat = $count * $this->center->lat / ($count + 1) + $latLon->lat / ($count + 1);
            $this->center->lon = $count * $this->center->lon / ($count + 1) + $latLon->lon / ($count + 1);
        }

        $this->points[] = $latLon;
        $this->popups[] = $popup;
        $this->types[] = $type;

        if (count($this->points) > 1) {
            $diff = 0;
            foreach ($this->points as $point) {
                if (abs($point->lat - $this->center->lat) > $diff) {
                    $diff = abs($point->lat - $this->center->lat);
                }
                if (abs($point->lon - $this->center->lon) > $diff) {
                    $diff = abs($point->lon - $this->center->lon);
                }
            }
            if ($diff > 0.01) {
                $this->zoom = 10;
            } elseif ($diff < 0.003) {
                $this->zoom = 11;
            } elseif ($diff < 0.001) {
                $this->zoom = 12;
            } else {
                $this->zoom = 13;
            }
        }
    }

    /**
     * @return array<array<int>>
     */
    public function getGroupsByLocation(): array
    {
        $groups = [];

        $latLons = [];
        foreach ($this->points as $key => $point) {
            $latLon = $point->lat . '-' . $point->lon;
            $latLons[$latLon][] = $key;
        }

        foreach ($latLons as $keys) {
            if (count($keys) > 1) {
                $groups[] = $keys;
            }
        }

        return $groups;
    }

    public function removeByIndex(int $index): void
    {
        unset($this->points[$index], $this->popups[$index], $this->types[$index]);
    }
}
