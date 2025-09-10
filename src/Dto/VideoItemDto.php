<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Pack;
use Carbon\Carbon;

class VideoItemDto extends PlaceDto
{
    // To report
    //    + PlaceDto
    public ?Carbon $dateAction = null;

    // To informant
    public ?string $organizationName = null;
    /** @var array<InformantDto> */
    public array $informants = [];

    // To fileMarker (and as additional for reportBlock)
    public ?string $notes = null;
    public ?int $category = null;
    public ?string $baseName = null;
    public ?string $localName = null;
    public ?string $youTube = null;
    public ?Pack $pack = null;
    public ?string $improvisation = null;
    public ?string $ritual = null;
    public ?string $tradition = null;
    public ?string $dateActionNotes = null;
    public ?string $texts = null;
    public ?string $tmkb = null;

    // To reportBlock
    /** @var array<int> $informantKeys */
    public array $informantKeys = [];
    public ?int $organizationKey = null;
    public ?int $reportKey = null;
    public ?int $blockKey = null;

    public function getHash(): string
    {
        $key = $this->geoPoint?->getLongBeName() . $this->place . $this->dateAction?->format('Ymd');
        $key .= $this->organizationName . $this->organizationKey . $this->reportKey;

        foreach ($this->informants as $informant) {
            $key .= $informant->name;
        }

        return hash('sha256', $key);
    }
}
