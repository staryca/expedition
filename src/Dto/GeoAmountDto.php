<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\GeoPoint;

class GeoAmountDto
{
    /** @var array<AmountDto> $regions */
    private array $regions = [];

    /** @var array<int, array<AmountDto>> $districts */
    private array $districts = [];
    private int $districtAmount = 0;

    /** @var array<int, array<string, PointAmountDto>> $places */
    private array $places = [];

    /**
     * @param string|null $name
     * @param array<AmountDto> $items
     * @return int|null
     */
    private function findItemByName(?string $name, array $items): ?int
    {
        if (empty($name)) {
            return null;
        }

        foreach ($items as $key => $item) {
            if ($item->getName() === $name) {
                return $key;
            }
        }

        return null;
    }

    public function increaseGeoPoint(GeoPoint $geoPoint): void
    {
        $region = $geoPoint->getRegion();
        $regionKey = $this->findItemByName($region, $this->regions);
        if ($regionKey === null && !empty($region)) {
            $regionKey = count($this->regions);
            $this->regions[$regionKey] = new AmountDto($regionKey, $region);
            $this->districts[$regionKey] = [];
        }

        if ($regionKey !== null) {
            $this->regions[$regionKey]->increaseAmount();

            $district = $geoPoint->getDistrict();
            $districtKey = $this->findItemByName($district, $this->districts[$regionKey]);
            if ($districtKey === null && !empty($district)) {
                $districtKey = $this->districtAmount++;
                $this->districts[$regionKey][$districtKey] = new AmountDto($districtKey, $district);
                $this->places[$districtKey] = [];
            }

            if ($districtKey !== null) {
                $this->districts[$regionKey][$districtKey]->increaseAmount();

                $geoId = $geoPoint->getId();
                if (!isset($this->places[$districtKey][$geoId])) {
                    $this->places[$districtKey][$geoId] = new PointAmountDto($geoId, $geoPoint->getMiddleBeName());
                }
                $this->places[$districtKey][$geoId]->increaseAmount();
            }
        }
    }

    public function getRegions(): array
    {
        return $this->regions;
    }

    public function getDistricts(): array
    {
        return array_merge(...$this->districts);
    }

    public function getPlaces(): array
    {
        return array_merge(...$this->places);
    }
}
