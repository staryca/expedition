<?php

declare(strict_types=1);

namespace App\Manager;

use App\Dto\GeoMapDto;
use App\Entity\Expedition;
use App\Entity\Informant;
use App\Entity\Report;
use App\Repository\GeoPointRepository;
use App\Repository\InformantRepository;
use App\Repository\ReportRepository;
use App\Repository\TaskRepository;

class GeoMapManager
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly GeoPointRepository $geoPointRepository,
        private readonly ReportRepository $reportRepository,
        private readonly InformantRepository $informantRepository,
    ) {
    }
    public function getGeoMapDataForExpedition(Expedition $expedition): GeoMapDto
    {
        $geoMapData = new GeoMapDto();

        $latLon = $expedition->getGeoPoint()?->getLatLonDto();
        if ($latLon) {
            $popup = 'База: ' . $expedition->getGeoPoint()?->getLongBeName();

            $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_BASE);
        }

        foreach ($expedition->getReports() as $report) {
            $latLon = $report->getLatLon();
            if ($latLon) {
                $popup = 'Справаздача за '
                    . ($report->getDateAction() ? $report->getDateAction()?->format('d.m.Y') : '?')
                    . ' (блокаў: ' . $report->getBlocks()->count() . ')';
                $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_REPORT);
            }
        }

        $informantsInTips = [];
        if ($expedition->getGeoPoint()) {
            $tips = $this->taskRepository->findTipsByInformantGeoPoint($expedition->getGeoPoint());
            foreach ($tips as $tip) {
                $latLon = $tip->getInformant()?->getGeoPointCurrent()?->getLatLonDto();
                $popup = 'Наводка: ' . $tip->getInformant()?->getFirstName() . ' (' . $tip->getContent() . ')';

                $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_TIP);

                if ($tip->getInformant()) {
                    $informantsInTips[] = $tip->getInformant()->getId();
                }
            }
        }

        if (count($geoMapData->points) === 1 && $expedition->getGeoPoint()) {
            $places = $this->geoPointRepository->findNotFarFromPoint($expedition->getGeoPoint());
            foreach ($places as $place) {
                $latLon = $place->getLatLonDto();
                $popup = $place->getLongBeName();

                $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_COMMENT);
            }
        }

        if ($expedition->getGeoPoint()) {
            $informants = $this->informantRepository->findNearCurrentGeoPoint($expedition->getGeoPoint());
            $groupedInformants = [];
            foreach ($informants as $informant) {
                if (!in_array($informant->getId(), $informantsInTips, true)) {
                    $key = $informant->getGeoPointCurrent()?->getId();
                    $groupedInformants[$key][] = $informant;
                }
            }
            /** @var array<int, array<Informant>> $groupedInformants */
            foreach ($groupedInformants as $informants) {
                $names = [];
                foreach ($informants as $informant) {
                    $names[] = $informant->getFirstName();
                }
                $latLon = $informants[0]->getGeoPointCurrent()?->getLatLonDto();
                $popup = 'Інфарманты: ' . implode(', ', $names);

                $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_COMMENT);
            }

            foreach ($this->reportRepository->findNearGeoPoint($expedition->getGeoPoint()) as $otherReport) {
                $latLon = $otherReport->getLatLon();
                if ($latLon && $expedition->getId() !== $otherReport->getExpedition()?->getId()) {
                    $popup = 'Іншая справаздача за '
                        . ($otherReport->getDateAction() ? $otherReport->getDateAction()?->format('d.m.Y') : '?')
                        . ' (блокаў: ' . $otherReport->getBlocks()->count() . ')';
                    $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_COMMENT);
                }
            }
        }

        // Group by location
        $geoMapData->groupByLocation();

        return $geoMapData;
    }

    public function getGeoMapDataForReport(Report $report): GeoMapDto
    {
        $geoMapData = new GeoMapDto();

        $latLonReport = $report->getGeoPoint()?->getLatLonDto();
        if ($latLonReport) {
            $popup = 'Гэта справаздача за '
                . ($report->getDateAction() ? $report->getDateAction()?->format('d.m.Y') : '?')
                . ' (блокаў: ' . $report->getBlocks()->count() . ')';

            $geoMapData->addLatLon($latLonReport, $popup, GeoMapDto::TYPE_REPORT);
        }

        $informantsInTips = [];
        foreach ($report->getTasks() as $task) {
            $latLon = $task->getInformant()?->getGeoPointCurrent()?->getLatLonDto();
            if ($latLon) {
                $popup = $task->getStatusText() . ': ' . $task->getContent() . '<br>Інфармант: ' . $task->getInformant()?->getFirstName();

                $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_TIP);
            }

            if ($task->getInformant()) {
                $informantsInTips[] = $task->getInformant()->getId();
            }
        }

        if ($report->getGeoPoint()) {
            $tips = $this->taskRepository->findTipsByInformantGeoPoint($report->getGeoPoint());
            foreach ($tips as $tip) {
                if (!$report->getTasks()->contains($tip)) {
                    $latLon = $tip->getInformant()?->getGeoPointCurrent()?->getLatLonDto();
                    $popup = 'Наводка: ' . $tip->getInformant()?->getFirstName() . ' (' . $tip->getContent() . ')';

                    $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_TIP);

                    if ($tip->getInformant()) {
                        $informantsInTips[] = $tip->getInformant()->getId();
                    }
                }
            }

            $informants = $this->informantRepository->findNearCurrentGeoPoint($report->getGeoPoint());
            $groupedInformants = [];
            foreach ($informants as $informant) {
                if (!in_array($informant->getId(), $informantsInTips, true)) {
                    $key = $informant->getGeoPointCurrent()?->getId();
                    $groupedInformants[$key][] = $informant;
                }
            }
            /** @var array<int, array<Informant>> $groupedInformants */
            foreach ($groupedInformants as $informants) {
                $names = [];
                foreach ($informants as $informant) {
                    $names[] = $informant->getFirstName();
                }
                $latLon = $informants[0]->getGeoPointCurrent()?->getLatLonDto();
                $popup = 'Інфарманты: ' . implode(', ', $names);

                $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_COMMENT);
            }

            foreach ($this->reportRepository->findNearGeoPoint($report->getGeoPoint()) as $otherReport) {
                $latLon = $otherReport->getLatLon();
                if ($latLon && $report->getId() !== $otherReport->getId()) {
                    $popup = (
                            $report->getExpedition()?->getId() === $otherReport->getExpedition()?->getId()
                            ? 'Справаздача'
                            : 'Іншая справаздача'
                        ) . ' за '
                        . ($otherReport->getDateAction() ? $otherReport->getDateAction()?->format('d.m.Y') : '?')
                        . ' (блокаў: ' . $otherReport->getBlocks()->count() . ')';
                    $type = $report->getExpedition()?->getId() === $otherReport->getExpedition()?->getId()
                        ? GeoMapDto::TYPE_REPORT
                        : GeoMapDto::TYPE_COMMENT;
                    $geoMapData->addLatLon($latLon, $popup, $type);
                }
            }
        }

        // Group by location
        $geoMapData->groupByLocation();

        if ($latLonReport) {
            $geoMapData->setCenter($latLonReport);
        }

        return $geoMapData;
    }
}
