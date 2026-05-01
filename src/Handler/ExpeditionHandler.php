<?php

declare(strict_types=1);

namespace App\Handler;

use App\Dto\SameByInformantDto;
use App\Dto\SameByOrganizationDto;
use App\Dto\SameReportsDto;
use App\Entity\Expedition;
use App\Entity\Task;
use App\Repository\ReportBlockRepository;
use App\Repository\ReportRepository;
use App\Repository\TaskRepository;

readonly class ExpeditionHandler
{
    public function __construct(
        private TaskRepository $taskRepository,
        private ReportRepository $reportRepository,
        private ReportBlockRepository $reportBlockRepository,
    ) {
    }

    /**
     * @param Expedition $expedition
     * @return array<Task>
     */
    public function getTips(Expedition $expedition): array
    {
        $result = [];
        if ($expedition->getGeoPoint()) {
            $tips = $this->taskRepository->findTipsByInformantGeoPoint($expedition->getGeoPoint());
            foreach ($tips as $tip) {
                if ($tip->getReport()?->getExpedition()->getId() !== $expedition->getId()) {
                    $result[] = $tip;
                }
            }
        }

        return $result;
    }

    public function getSameReports(Expedition $expedition): array
    {
        $sameReportsDtos = [];
        foreach ($expedition->getReports() as $report) {
            $same = $this->reportRepository->findSameReports($report);
            if (!empty($same)) {
                $sameReportsDtos[] = new SameReportsDto($report, $same);
            }
        }

        return $sameReportsDtos;
    }

    public function getSameByInformants(Expedition $expedition): array
    {
        $informants = [];
        $blocks = $this->reportBlockRepository->findByExpedition($expedition);
        foreach ($blocks as $block) {
            foreach ($block->getInformants() as $informant) {
                $informants[$informant->getId()] = $informant;
            }
        }

        $sameByInformants = [];
        foreach ($informants as $informant) {
            $blocks = $this->reportBlockRepository->findByInformant($informant, $expedition);
            if (!empty($blocks)) {
                $sameByInformants[] = new SameByInformantDto($informant, $blocks);
            }
        }

        return $sameByInformants;
    }

    public function getSameByOrganization(Expedition $expedition): array
    {
        $organizations = [];
        $blocks = $this->reportBlockRepository->findByExpedition($expedition);
        foreach ($blocks as $block) {
            $organization = $block->getOrganization();
            if (!empty($organization)) {
                $organizations[$organization->getId()] = $organization;
            }
        }

        $sameByOrganizations = [];
        foreach ($organizations as $organization) {
            $blocks = $this->reportBlockRepository->findByOrganization($organization, $expedition);
            if (!empty($blocks)) {
                $sameByOrganizations[] = new SameByOrganizationDto($organization, $blocks);
            }
        }

        return $sameByOrganizations;
    }
}
