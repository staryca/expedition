<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Report;

readonly class SameReportsDto
{
    public function __construct(
        private Report $report,
        private array $sameReports,
    ) {
    }

    public function getReport(): Report
    {
        return $this->report;
    }

    public function getSameReports(): array
    {
        return $this->sameReports;
    }
}
