<?php

declare(strict_types=1);

namespace App\Dto;

use App\Parser\Columns\KoboContentColumns;

class ContentDto
{
    public ?string $notes = null;
    public ?int $reportIndex = null;

    public static function fromKobo(array $data): self
    {
        $dto = new self();
        $dto->reportIndex = $data[KoboContentColumns::INDEX_REPORT] ? (int) $data[KoboContentColumns::INDEX_REPORT] : null;
        $dto->notes = $data[KoboContentColumns::NOTES] ?? null;

        return $dto;
    }
}
