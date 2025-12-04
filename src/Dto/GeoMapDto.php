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

    private const COLOR_TYPES = [
        self::TYPE_REPORT => 'green',
        self::TYPE_BASE => 'violet',
        self::TYPE_TIP => 'red',
        self::TYPE_COMMENT => 'grey',
        self::TYPE_COMPLEX => 'black',
    ];

    public int $zoom = 14;
    public ?LatLonDto $center = null;
    /** @var array<LatLonDto> $points */
    public array $points = [];
    /** @var array<string> $popups */
    public array $popups = [];
    /** @var array<?int> $types */
    public array $types = [];
    /** @var array<string> $colors */
    public array $colors = [];

    public function addLatLon(LatLonDto $latLon, string $popup, ?int $type, ?string $color = null): void
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
        $this->colors[] = $type ? self::COLOR_TYPES[$type] : $color;

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
    private function getGroupsByLocation(): array
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

    public function groupByLocation(): void
    {
        $groups = $this->getGroupsByLocation();
        foreach ($groups as $keys) {
            $latLon = clone $this->points[current($keys)];

            $types = [];
            $colors = [];
            $popup = '<ul>';
            foreach ($keys as $key) {
                $popup .= '<li>' . $this->popups[$key] . '</li>';
                if ($this->types[$key]) {
                    $types[$this->types[$key]] = 1;
                } else {
                    $colors[$this->colors[$key]] = 1;
                }

                $this->removeByIndex($key);
            }
            $popup .= '</ul>';

            $type = null;
            $color = null;
            if (count($types) > 0 && count($colors) > 0) {
                $type = self::TYPE_COMPLEX;
            } elseif (count($types) > 0) {
                if (isset($types[self::TYPE_COMMENT]) && count($types) > 1) {
                    unset($types[self::TYPE_COMMENT]);
                }
                $type = isset($types[self::TYPE_TIP]) ? self::TYPE_TIP : null;
                $type = $type ?? (count($types) > 1 ? self::TYPE_COMPLEX : key($types));
            } else {
                if (count($colors) > 1) {
                    $type = self::TYPE_COMPLEX;
                } else {
                    $color = current($colors);
                }
            }

            $this->addLatLon($latLon, $popup, $type, $color);
        }
    }

    public function removeByIndex(int $index): void
    {
        unset($this->points[$index], $this->popups[$index], $this->types[$index], $this->colors[$index]);
    }

    public function setCenter(LatLonDto $center): void
    {
        $this->center = $center;
    }

    public function getColors(): array
    {
        return array_map(function ($type) {
            return self::COLOR_TYPES[$type];
        }, $this->types);
    }
}
