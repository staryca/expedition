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
use App\Service\LocationService;
use Twig\Environment;

class GeoMapManager
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly GeoPointRepository $geoPointRepository,
        private readonly ReportRepository $reportRepository,
        private readonly InformantRepository $informantRepository,
        private readonly LocationService $locationService,
        private readonly Environment $twig,
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
                $popup = $this->twig->render(
                    'part/geo_map/report.html.twig',
                    ['report' => $report, 'expedition' => $expedition]
                );
                $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_REPORT);
            }
        }

        $informantsInTips = [];
        if ($expedition->getGeoPoint()) {
            $tips = $this->taskRepository->findTipsByInformantGeoPoint($expedition->getGeoPoint());
            foreach ($tips as $tip) {
                $latLon = $tip->getInformant()?->getGeoPointCurrent()?->getLatLonDto();
                $popup = $this->twig->render('part/geo_map/tip.html.twig', ['tip' => $tip]);

                $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_TIP);

                if ($tip->getInformant()) {
                    $informantsInTips[] = $tip->getInformant()->getId();
                }
            }
        }

        $isPreview = count($geoMapData->points) === 1 && $expedition->getGeoPoint();
        if ($isPreview) {
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

                $geoMapData->addLatLon($latLon, $popup, $isPreview ? GeoMapDto::TYPE_COMPLEX : GeoMapDto::TYPE_COMMENT);
            }

            foreach ($this->reportRepository->findNearGeoPoint($expedition->getGeoPoint()) as $otherReport) {
                $latLon = $otherReport->getLatLon();
                if ($latLon && $expedition->getId() !== $otherReport->getExpedition()?->getId()) {
                    $popup = $this->twig->render(
                        'part/geo_map/report.html.twig',
                        ['report' => $otherReport, 'expedition' => $expedition]
                    );
                    $geoMapData->addLatLon($latLon, $popup, $isPreview ? GeoMapDto::TYPE_COMPLEX : GeoMapDto::TYPE_COMMENT);
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

        $reportPoint = $report->getGeoPoint();
        if (!$reportPoint && !empty($report->getGeoPlace())) {
            $reportPoint = $this->locationService->detectLocationByFullPlace($report->getGeoPlace());
        }

        $latLonReport = $reportPoint?->getLatLonDto();
        if ($latLonReport) {
            $popup = $this->twig->render(
                'part/geo_map/report.html.twig',
                ['report' => $report, 'expedition' => $report->getExpedition(), 'isThis' => true]
            );
            $geoMapData->addLatLon($latLonReport, $popup, GeoMapDto::TYPE_BASE);
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

        if ($reportPoint) {
            $tips = $this->taskRepository->findTipsByInformantGeoPoint($reportPoint);
            foreach ($tips as $tip) {
                if (!$report->getTasks()->contains($tip)) {
                    $latLon = $tip->getInformant()?->getGeoPointCurrent()?->getLatLonDto();
                    $popup = $this->twig->render('part/geo_map/tip.html.twig', ['tip' => $tip]);

                    $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_TIP);

                    if ($tip->getInformant()) {
                        $informantsInTips[] = $tip->getInformant()->getId();
                    }
                }
            }

            $informants = $this->informantRepository->findNearCurrentGeoPoint($reportPoint);
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

            foreach ($this->reportRepository->findNearGeoPoint($reportPoint) as $otherReport) {
                $latLon = $otherReport->getLatLon();
                if ($latLon && $report->getId() !== $otherReport->getId()) {
                    $popup = $this->twig->render(
                        'part/geo_map/report.html.twig',
                        ['report' => $otherReport, 'expedition' => $report->getExpedition()]
                    );
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
