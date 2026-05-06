<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Organization;

readonly class SameByOrganizationDto
{
    public function __construct(
        private Organization $organization,
        private array $sameReportBlocks,
    ) {
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getSameReportBlocks(): array
    {
        return $this->sameReportBlocks;
    }
}
