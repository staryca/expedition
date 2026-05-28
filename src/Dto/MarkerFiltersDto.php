<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Dance;
use App\Entity\GeoPoint;
use App\Entity\Ritual;
use App\Entity\Tag;

class MarkerFiltersDto
{
    private const string CATEGORIES = 'categories';
    private const string DANCES = 'dances';
    private const string REGIONS = 'regions';
    private const string DISTRICTS = 'districts';
    private const string RITUALS = 'rituals';
    private const string PLACES = 'places';
    private const string PACKS = 'packs';

    private int $amountItems;

    /** @var array<int, int> $categories */
    private array $categories = [];

    /** @var array<int, AmountDto $rituals */
    private array $rituals = [];

    /** @var array<int, AmountDto $dances */
    private array $dances = [];

    /** @var array<string, int> $packs */
    private array $packs = [];

    /** @var array<int, AmountDto $packAmounts */
    private array $packAmounts = [];

    /** @var array<string, int> $improvisations */
    private array $improvisations = [];

    /** @var array<int, AmountDto> $tags */
    private array $tags = [];

    private array $selected = [];

    private GeoAmountDto $geoAmounts;

    public function __construct(int $amountItems)
    {
        $this->geoAmounts = new GeoAmountDto();
        $this->amountItems = $amountItems;
    }

    public function getAmountItems(): int
    {
        return $this->amountItems;
    }

    public function incCategory(int $categoryId): void
    {
        if (!isset($this->categories[$categoryId])) {
            $this->categories[$categoryId] = 1;
        } else {
            $this->categories[$categoryId] += 1;
        }
    }

    /**
     * @return array<int, int>
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function incRitual(Ritual $ritual): void
    {
        if (!isset($this->rituals[$ritual->getId()])) {
            $this->rituals[$ritual->getId()] = new AmountDto($ritual->getId(), $ritual->getName());
        }
        $this->rituals[$ritual->getId()]->increaseAmount();
    }

    /**
     * @return array<int, AmountDto>
     */
    public function getRituals(): array
    {
        return $this->rituals;
    }

    public function incDance(Dance $dance): void
    {
        if (!isset($this->dances[$dance->getId()])) {
            $this->dances[$dance->getId()] = new AmountDto($dance->getId(), $dance->getName());
        }
        $this->dances[$dance->getId()]->increaseAmount();
    }

    /**
     * @return array<int, AmountDto>
     */
    public function getDances(): array
    {
        return $this->dances;
    }

    public function incPack(string $pack): void
    {
        if (!isset($this->packs[$pack])) {
            $this->packs[$pack] = 1;
        } else {
            $this->packs[$pack] += 1;
        }
    }

    public function getPacks(): array
    {
        return $this->packs;
    }

    /**
     * @return array<int, AmountDto>
     */
    public function getPackAmounts(): array
    {
        return $this->packAmounts;
    }

    public function incTag(Tag $tag): void
    {
        if (!isset($this->tags[$tag->getId()])) {
            $this->tags[$tag->getId()] = new AmountDto($tag->getId(), $tag->getName());
        }
        $this->tags[$tag->getId()]->increaseAmount();
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function incImprovisation(string $improvisation): void
    {
        if (!isset($this->improvisations[$improvisation])) {
            $this->improvisations[$improvisation] = 1;
        } else {
            $this->improvisations[$improvisation] += 1;
        }
    }

    public function getImprovisations(): array
    {
        return $this->improvisations;
    }

    public function incGeoPoint(GeoPoint $geoPoint): void
    {
        $this->geoAmounts->increaseGeoPoint($geoPoint);
    }

    public function getGeoAmounts(): GeoAmountDto
    {
        return $this->geoAmounts;
    }

    public function setSelected(array $selected): void
    {
        $this->selected = $selected;
    }

    public function selectCategory(int $category): void
    {
        $this->selected[self::CATEGORIES][$category] = 1;
    }

    /**
     * @return array<int>
     */
    public function getSelectedCategories(): array
    {
        return $this->getSelectedItems(self::CATEGORIES);
    }

    public function selectDance(int $id): void
    {
        $this->selected[self::DANCES][$id] = 1;
    }

    /**
     * @return array<int>
     */
    public function getSelectedDances(): array
    {
        return $this->getSelectedItems(self::DANCES);
    }

    /**
     * @return array<string>
     */
    public function getSelectedRegions(): array
    {
        if (empty($this->selected[self::REGIONS])) {
            return [];
        }

        return $this->geoAmounts->getSomeRegions(array_keys($this->selected[self::REGIONS]));
    }

    /**
     * @return array<string>
     */
    public function getSelectedDistricts(): array
    {
        if (empty($this->selected[self::DISTRICTS])) {
            return [];
        }

        return $this->geoAmounts->getSomeDistricts(array_keys($this->selected[self::DISTRICTS]));
    }

    public function setRegionKeys(array $regions): void
    {
        $this->geoAmounts->setRegionKeys($regions);
    }

    public function setDistrictKeys(array $districts): void
    {
        $this->geoAmounts->setDistrictKeys($districts);
    }

    /**
     * @return array<int>
     */
    public function getSelectedRituals(): array
    {
        return $this->getSelectedItems(self::RITUALS);
    }

    public function getSelectedPlaces(): array
    {
        return $this->getSelectedItems(self::PLACES);
    }

    public function getSelectedPacks(): array
    {
        return $this->getSelectedItems(self::PACKS);
    }

    /**
     * @return array<string>
     */
    public function getSelectedPackNames(): array
    {
        $names = [];

        $ids = $this->getSelectedItems(self::PACKS);

        foreach ($this->packAmounts as $packAmount) {
            if (in_array($packAmount->getId(), $ids)) {
                $names[] = $packAmount->getName();
            }
        }

        return $names;
    }

    private function getSelectedItems(string $type): array
    {
        if (empty($this->selected[$type])) {
            return [];
        }

        return array_keys($this->selected[$type]);
    }

    /**
     * @param array<int, string> $packs
     * @return void
     */
    public function setPackKeys(array $packs): void
    {
        $this->packAmounts = [];

        foreach ($this->packs as $pack => $amount) {
            $key = array_search($pack, $packs);
            if ($key !== false) {
                $this->packAmounts[$key] = new AmountDto($key, $pack);
                $this->packAmounts[$key]->setAmount($amount);
            }
        }
    }
}
