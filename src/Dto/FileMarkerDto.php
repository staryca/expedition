<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Ritual;
use Carbon\Carbon;

class FileMarkerDto extends PlaceDto
{
    // To reportBlock
    public const OTHER_RECORD = 'record';
    // To informant
    public const OTHER_BIRTH_GEO_POINT = 'birth_geo_point';
    public const OTHER_BIRTH_LOCATION = 'birth_location';
    // To ...
    public const OTHER_MENTION = 'mention';

    // To report
    public ?Carbon $dateAction = null;

    // To informant
    public ?string $informantsText = null;

    // To fileMarker (and as additional for reportBlock)
    public ?string $timeFrom = null;
    public ?string $timeTo = null;
    public ?string $name = null;
    public ?string $notes = null;
    public array $additional = []; // Keys in FileMarkerAdditional
    public ?Ritual $ritual = null;
    public ?string $decoding = null;
    public ?int $category = null;
    public bool $isNewBlock = false;

    // To reportBlock
    /** @var array<int> $informantKeys */
    public array $informantKeys = [];
    public ?int $organizationKey = null;
    public ?int $reportKey = null;
    public ?int $blockKey = null;

    // Many other
    public array $others = [];

    public function getBirthPlace(): ?PlaceDto
    {
        if (!isset($this->others[self::OTHER_BIRTH_GEO_POINT]) && !isset($this->others[self::OTHER_BIRTH_LOCATION])) {
            return null;
        }

        $dto = new PlaceDto();
        $dto->geoPoint = $this->others[self::OTHER_BIRTH_GEO_POINT] ?? null;
        $dto->place = $this->others[self::OTHER_BIRTH_LOCATION] ?? null;

        return $dto;
    }
}
