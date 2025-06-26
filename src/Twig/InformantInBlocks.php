<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Informant;
use App\Repository\ReportBlockRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class InformantInBlocks extends AbstractExtension
{
    public function __construct(
        private readonly ReportBlockRepository $reportBlockRepository,
    ) {
    }
    public function getFilters(): array
    {
        return [
            new TwigFilter('is_in_blocks', [$this, 'isInBlocks']),
        ];
    }

    public function isInBlocks(?Informant $informant): string
    {
        if (null === $informant) {
            return '';
        }

        $blocks = $this->reportBlockRepository->findByInformant($informant);

        return count($blocks) > 0 ? 'Апрацаваны' : '';
    }
}
