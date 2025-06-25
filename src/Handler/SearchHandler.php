<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\ReportBlock;
use App\Service\SearchService;
use Doctrine\ORM\EntityManagerInterface;

class SearchHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SearchService $searchService,
    ) {
    }

    public function generateSearchForBlock(ReportBlock $reportBlock): void
    {
        $this->searchService->generateSearchForBlock($reportBlock);

        $this->entityManager->flush();
    }
}
