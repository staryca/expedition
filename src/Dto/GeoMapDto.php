<?php

declare(strict_types=1);

namespace App\Dto;

class GeoMapDto
{
    public int $zoom = 14;
    public ?LatLonDto $center = null;
    /** @var array<LatLonDto> $points */
    public array $points = [];
    /** @var array<string> $popups */
    public array $popups = [];

    public function addLatLon(LatLonDto $latLon, string $popup): void
    {
        if (null === $this->center) {
            $this->center = $latLon;
        } else {
            $count = count($this->points);
            $this->center->lat = $count * $this->center->lat / ($count + 1) + $latLon->lat / ($count + 1);
            $this->center->lon = $count * $this->center->lon / ($count + 1) + $latLon->lon / ($count + 1);
        }

        $this->points[] = $latLon;
        $this->popups[] = $popup;

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
}
