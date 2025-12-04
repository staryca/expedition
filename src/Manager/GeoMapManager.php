<?php

declare(strict_types=1);

namespace App\Manager;

use App\Dto\GeoMapDto;
use App\Entity\Expedition;
use App\Entity\GeoPoint;
use App\Entity\Informant;
use App\Entity\Report;
use App\Repository\GeoPointRepository;
use App\Repository\InformantRepository;
use App\Repository\ReportRepository;
use App\Repository\TaskRepository;
use App\Service\LocationService;
use Twig\Environment;

readonly class GeoMapManager
{
    public function __construct(
        private TaskRepository $taskRepository,
        private GeoPointRepository $geoPointRepository,
        private ReportRepository $reportRepository,
        private InformantRepository $informantRepository,
        private LocationService $locationService,
        private Environment $twig,
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
            $informants = $this->informantRepository->findCurrentInLocation($expedition->getGeoPoint());
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

            $informants = $this->informantRepository->findCurrentInLocation($reportPoint);
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
                $geoCurrent = $informants[0]->getGeoPointCurrent();
                $latLon = $geoCurrent?->getLatLonDto();
                $popup = $this->twig->render(
                    'part/geo_map/informants.html.twig',
                    ['names' => $names, 'location' => $geoCurrent?->getShortBeName(), 'geoPointId' => $geoCurrent?->getId()]
                );

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

    public function getGeoMapDataForGeoPoint(GeoPoint $geoPoint): GeoMapDto
    {
        $geoMapData = new GeoMapDto();

        $latLonPoint = $geoPoint->getLatLonDto();
        if ($latLonPoint) {
            $popup = $geoPoint->getLongBeName();
            $geoMapData->addLatLon($latLonPoint, $popup, GeoMapDto::TYPE_BASE);
        }

        foreach ($this->reportRepository->findNearGeoPoint($geoPoint, LocationService::POINT_NEAR) as $report) {
            $latLon = $report->getLatLon();
            if ($latLon) {
                $popup = $this->twig->render(
                    'part/geo_map/report.html.twig',
                    ['report' => $report]
                );
                $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_REPORT);
            }
        }

        $informantsInTips = [];
        $tips = $this->taskRepository->findTipsByInformantGeoPoint($geoPoint, LocationService::POINT_NEAR);
        foreach ($tips as $tip) {
            $latLon = $tip->getInformant()?->getGeoPointCurrent()?->getLatLonDto();
            $popup = $this->twig->render('part/geo_map/tip.html.twig', ['tip' => $tip]);

            $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_TIP);

            if ($tip->getInformant()) {
                $informantsInTips[] = $tip->getInformant()->getId();
            }
        }

        $isPreview = count($geoMapData->points) === 1;
        if ($isPreview) {
            $places = $this->geoPointRepository->findNotFarFromPoint($geoPoint, LocationService::POINT_NEAR);
            foreach ($places as $place) {
                $latLon = $place->getLatLonDto();
                $popup = $place->getLongBeName();

                $geoMapData->addLatLon($latLon, $popup, GeoMapDto::TYPE_COMMENT);
            }
        }

        $informants = $this->informantRepository->findCurrentInLocation($geoPoint, LocationService::POINT_NEAR);
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
            $geoCurrent = $informants[0]->getGeoPointCurrent();
            $latLon = $geoCurrent?->getLatLonDto();
            if ($latLon) {
                $popup = $this->twig->render(
                    'part/geo_map/informants.html.twig',
                    ['names' => $names, 'location' => $geoCurrent?->getShortBeName(), 'geoPointId' => $geoCurrent?->getId()]
                );
                $geoMapData->addLatLon($latLon, $popup, $isPreview ? GeoMapDto::TYPE_COMPLEX : GeoMapDto::TYPE_COMMENT);
            }
        }

        // Group by location
        $geoMapData->groupByLocation();

        if ($latLonPoint) {
            $geoMapData->setCenter($latLonPoint);
        }

        return $geoMapData;
    }

    public function getGeoMapDataForMarkers(Expedition $expedition): GeoMapDto
    {
        $geoMapData = new GeoMapDto();

        foreach ($expedition->getReports() as $report) {
            $latLon = $report->getLatLon();
            if ($latLon) {
                $popup = $report->getShortGeoPlace();
                foreach ($report->getBlocks() as $block) {
                    foreach ($block->getFileMarkers() as $fileMarker) {
                        $color = $fileMarker->getAdditionalValue('color');
                        $type = empty($color) ? GeoMapDto::TYPE_REPORT : null;
                        $geoMapData->addLatLon($latLon, $popup, $type, $color);
                    }
                }
            }
        }

        // Group by location
        $geoMapData->groupByLocation();

        return $geoMapData;
    }
}
