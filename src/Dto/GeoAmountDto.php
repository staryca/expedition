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

    /** @var array<int, string> $districtKeys */
    private array $districtKeys = [];

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

    /**
     * @return AmountDto[]
     */
    public function getRegionAmounts(): array
    {
        return $this->regions;
    }

    /**
     * @param array<int> $regionKeys
     * @return array<int, string>
     */
    public function getSomeRegions(array $regionKeys): array
    {
        $regions = [];

        foreach ($this->regions as $amountDto) {
            if (in_array($amountDto->getId(), $regionKeys)) {
                $regions[$amountDto->getId()] = $amountDto->getName();
            }
        }

        return $regions;
    }

    /**
     * @return array<int, AmountDto>
     */
    public function getDistricts(): array
    {
        $districts = [];

        foreach ($this->districts as $districtsInRegion) {
            foreach ($districtsInRegion as $amountDto) {
                $key = array_search($amountDto->getName(), $this->districtKeys, true);
                if ($key !== false) {
                    $amountDto->updateId($key);
                    $districts[$key] = $amountDto;
                }
            }
        }

        return $districts;
    }

    /**
     * @param array<int> $districtKeys
     * @return array<string>
     */
    public function getSomeDistricts(array $districtKeys): array
    {
        $selected = array_filter(
            $this->getDistricts(),
            function ($districtKey) use ($districtKeys): bool {
                return in_array($districtKey, $districtKeys);
            },
            ARRAY_FILTER_USE_KEY
        );

        return array_map(function ($district) {
            return $district->getName();
        }, $selected);
    }

    public function getPlaces(): array
    {
        return array_merge(...$this->places);
    }

    /**
     * @param array<int, string> $regions
     * @return void
     */
    public function setRegionKeys(array $regions): void
    {
        foreach ($this->regions as $amountDto) {
            $key = array_search($amountDto->getName(), $regions, true);
            if (false !== $key) {
                $amountDto->updateId($key);
            }
        }
    }

    public function setDistrictKeys(array $districts): void
    {
        $this->districtKeys = $districts;
    }
}
