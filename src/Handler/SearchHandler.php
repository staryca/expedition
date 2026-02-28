<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\ReportBlock;
use App\Service\SearchService;

readonly class SearchHandler
{
    public function __construct(
        private SearchService $searchService,
    ) {
    }

    public function generateSearchForBlock(ReportBlock $reportBlock): void
    {
        $this->searchService->generateSearchForBlock($reportBlock);
    }
}
