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

    private int $amountItems;

    /** @var array<int, int> $categories */
    private array $categories = [];

    /** @var array<int, AmountDto $rituals */
    private array $rituals = [];

    /** @var array<int, AmountDto $dances */
    private array $dances = [];

    /** @var array<string, int> $packs */
    private array $packs = [];

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

    public function getSelectedCategories(): array
    {
        if (empty($this->selected[self::CATEGORIES])) {
            return [];
        }

        return array_keys($this->selected[self::CATEGORIES]);
    }

    public function selectDance(int $id): void
    {
        $this->selected[self::DANCES][$id] = 1;
    }

    public function getSelectedDances(): array
    {
        if (empty($this->selected[self::DANCES])) {
            return [];
        }

        return array_keys($this->selected[self::DANCES]);
    }
}
