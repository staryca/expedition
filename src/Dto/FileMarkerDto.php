<?php

declare(strict_types=1);

namespace App\Dto;

class FileMarkerDto extends PlaceDto
{
    public ?string $timeFrom = null;
    public ?string $timeTo = null;
    public ?string $name = null;
    public ?string $notes = null;
    public ?string $decoding = null;
    public ?string $informantsText = null;
    public ?int $category = null;
    public bool $isNewBlock = false;

    public ?int $reportKey = null;
    public ?int $blockKey = null;
}
