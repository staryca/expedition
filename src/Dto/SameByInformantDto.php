<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Informant;

readonly class SameByInformantDto
{
    public function __construct(
        private Informant $informant,
        private array     $sameReportBlocks,
    ) {
    }

    public function getInformant(): Informant
    {
        return $this->informant;
    }

    public function getSameReportBlocks(): array
    {
        return $this->sameReportBlocks;
    }
}
