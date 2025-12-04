<?php

declare(strict_types=1);

namespace App\Manager;

use App\Dto\FileDto;
use App\Dto\ReportBlockDataDto;
use App\Dto\ReportDataDto;
use App\Entity\Type\ReportBlockType;
use App\Service\PersonService;
use Carbon\Carbon;

readonly class FileManager
{
    public function __construct(
        private PersonService $personService,
    ) {
    }

    /**
     * @param array<FileDto> $files
     * @return array<ReportDataDto>
     */
    public function createReports(array &$files, Carbon $dateCreated): array
    {
        $reports = [];
        $reportKey = -1;
        $blockKey = -1;

        foreach ($files as $file) {
            foreach ($file->markers as $marker) {
                if ($marker->isNewBlock) {
                    if (
                        $reportKey === -1
                        || $reports[$reportKey]->geoPoint !== $marker->geoPoint
                        || $reports[$reportKey]->place !== $marker->place
                    ) {
                        $reportKey++;
                        $reports[$reportKey] = new ReportDataDto();
                        $reports[$reportKey]->geoPoint = $marker->geoPoint;
                        $reports[$reportKey]->place = $marker->place;
                        $reports[$reportKey]->dateCreated = $dateCreated;
                        $blockKey = 0;
                    } else {
                        $blockKey++;
                    }
                    $reports[$reportKey]->blocks[$blockKey] = new ReportBlockDataDto();
                    $reports[$reportKey]->blocks[$blockKey]->type = ReportBlockType::TYPE_CONVERSATION;

                    if (!empty($marker->informantsText)) {
                        $reports[$reportKey]->blocks[$blockKey]->informants =
                            $this->personService->getInformants($marker->informantsText);
                    }
                }
                $marker->reportKey = $reportKey;
                $marker->blockKey = $blockKey;
            }
        }

        return $reports;
    }
}
