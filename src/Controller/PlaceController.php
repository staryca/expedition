<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LocationGroupDto;
use App\Entity\FileMarker;
use App\Entity\GeoPoint;
use App\Entity\Type\CategoryType;
use App\Manager\GeoMapManager;
use App\Repository\GeoPointRepository;
use App\Repository\InformantRepository;
use App\Repository\OrganizationRepository;
use App\Repository\ReportRepository;
use App\Repository\SubjectRepository;
use App\Repository\TaskRepository;
use App\Service\InformantService;
use App\Service\LocationService;
use App\Service\MarkerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlaceController extends AbstractController
{
    public function __construct(
        private readonly ReportRepository $reportRepository,
        private readonly GeoPointRepository $geoPointRepository,
        private readonly InformantRepository $informantRepository,
        private readonly OrganizationRepository $organizationRepository,
        private readonly SubjectRepository $subjectRepository,
        private readonly TaskRepository $taskRepository,
        private readonly MarkerService $markerService,
        private readonly InformantService $informantService,
        private readonly GeoMapManager $geoMapManager,
    ) {
    }

    #[Route('/place/list', name: 'place_list')]
    public function list(): Response
    {
        $points = [];
        $reports = $this->reportRepository->findAllActive();
        foreach ($reports as $report) {
            if ($report->getGeoPoint() !== null && !isset($points[$report->getGeoPoint()->getId()])) {
                $points[$report->getGeoPoint()->getId()] = $report->getGeoPoint();
            }
        }

        $locations = new LocationGroupDto();
        foreach ($points as $point) {
            $region = $point->getRegion();

            $location = new LocationGroupDto();
            $location->id = $point->getId();
            $location->name = $point->getLongBeName();

            if (empty($region)) {
                $locations->addItem($location);
                continue;
            }

            $district = $point->getDistrict();
            $locationRegion = $locations->getOrCreateGroup($region);
            if (!empty($district)) {
                $locationDistrict = $locationRegion->getOrCreateGroup($district);
                $locationDistrict->addItem($location);
            } else {
                $locationRegion->addItem($location);
            }
        }

        return $this->render('place/list.html.twig', [
            'locations' => $locations,
        ]);
    }

    private function getGeoPoint(int $id): GeoPoint
    {
        /** @var GeoPoint|null $geoPoint */
        $geoPoint = $this->geoPointRepository->find($id);
        if (!$geoPoint) {
            throw $this->createNotFoundException('The GeoPoint does not exist');
        }

        return $geoPoint;
    }

    #[Route('/place/item/{id}', name: 'place_item')]
    public function item(int $id): Response
    {
        $geoPoint = $this->getGeoPoint($id);

        $reports = $this->reportRepository->findByGeoPoint($geoPoint);

        $informants = $this->informantRepository->findByGeoPoint($geoPoint);

        $organizations = $this->organizationRepository->findByGeoPoint($geoPoint);

        $subjects = $this->subjectRepository->findByGeoPoint($geoPoint);

        $tasks = $this->taskRepository->findByReportGeoPoint($geoPoint);

        $markerGroups = $this->markerService->getGroupedMarkersByGeoPoint($geoPoint);

        return $this->render('place/item.show.html.twig', [
            'geoPoint' => $geoPoint,
            'reports' => $reports,
            'informants' => $informants,
            'organizations' => $organizations,
            'subjects' => $subjects,
            'tasks' => $tasks,
            'markerGroups' => $markerGroups,
            'categories' => CategoryType::getManyNames(false),
        ]);
    }

    #[Route('/place/item/{id}/near', name: 'place_item_near')]
    public function itemNear(int $id): Response
    {
        $geoPoint = $this->getGeoPoint($id);

        $markerGroups = $this->markerService->getGroupedMarkersNearGeoPoint($geoPoint);

        $geoMapData = $this->geoMapManager->getGeoMapDataForGeoPoint($geoPoint);

        return $this->render('place/item.near.show.html.twig', [
            'title' => 'Уся інфармацыя вакол гэтага населенага пункта (каля '
                . LocationService::getDistance(LocationService::POINT_NEAR) . ' км)',
            'geoPoint' => $geoPoint,
            'markerGroups' => $markerGroups,
            'categories' => CategoryType::getManyNames(false),
            'geoMapData' => $geoMapData,
        ]);
    }

    #[Route('/place/item/{id}/near/songs', name: 'place_item_near_songs')]
    public function itemNearSongs(int $id): Response
    {
        return $this->itemNearSongsByType($id, 'show');
    }

    #[Route('/place/item/{id}/near/songs/print', name: 'place_item_near_songs_print')]
    public function itemNearSongsPrint(int $id): Response
    {
        return $this->itemNearSongsByType($id, 'print');
    }

    private function itemNearSongsByType(int $id, string $type): Response
    {
        $geoPoint = $this->getGeoPoint($id);

        $markersByCategory = $this->markerService->getSongsNearGeoPoint($geoPoint);

        $markerGroups = [];
        $categories = [];
        $key = 1000;
        foreach ($markersByCategory as $category => $markers) {
            $categories[$key] = $category;
            $markerGroups[$key] = $markers;
            $key++;
        }

        $geoMapData = [];

        return $this->render('place/item.near.' . $type . '.html.twig', [
            'title' => 'Усе песьні вакол гэтага населенага пункта',
            'geoPoint' => $geoPoint,
            'markerGroups' => $markerGroups,
            'categories' => $categories,
            'geoMapData' => $geoMapData,
        ]);
    }


    #[Route('/place/item/{id}/near/songs/export', name: 'place_item_near_songs_export')]
    public function itemNearSongsExport(int $id): Response
    {
        $geoPoint = $this->getGeoPoint($id);

        $markersByCategory = $this->markerService->getSongsNearGeoPoint($geoPoint);

        $csv = $this->markerService->generateCsvFromMarkers($markersByCategory);
        $content = $csv->toString();

        $response = new Response($content);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $geoPoint->getId() . '_songs.csv'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/place/item/{id}/near/other/export', name: 'place_item_near_other_export')]
    public function itemNearOtherExport(int $id): Response
    {
        $geoPoint = $this->getGeoPoint($id);

        $markersByCategory = [];
        $markerGroups = $this->markerService->getGroupedMarkersNearGeoPoint($geoPoint);
        foreach ($markerGroups as $category => $markers) {
            if ($category === CategoryType::SONGS || CategoryType::isSystemType($category)) {
                continue;
            }

            $markersByCategory[CategoryType::getManyOrSingleName($category)] = $markers;
        }

        $csv = $this->markerService->generateCsvFromMarkers($markersByCategory);
        $content = $csv->toString();

        $response = new Response($content);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $geoPoint->getId() . '_other.csv'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/place/item/{id}/near/informants', name: 'place_item_near_informants')]
    public function itemNearInformants(int $id): Response
    {
        $geoPoint = $this->getGeoPoint($id);
        $radius = LocationService::POINT_NEAR;

        $informantsByLocation = $this->informantService->getInformantsNearGeoPoint($geoPoint, $radius);

        $informantGroups = [];
        $groups = [];
        $key = 1000;
        foreach ($informantsByLocation as $location => $informants) {
            $groups[$key] = $location;
            $informantGroups[$key] = $informants;
            $key++;
        }

        $geoMapData = [];

        return $this->render('place/informants.near.show.html.twig', [
            'title' => 'Усе інфарманты вакол гэтага населенага пункта (каля '
                . LocationService::getDistance($radius) . ' км)',
            'geoPoint' => $geoPoint,
            'informantGroups' => $informantGroups,
            'groups' => $groups,
            'geoMapData' => $geoMapData,
        ]);
    }

    #[Route('/place/item/{id}/near/informants/export', name: 'place_item_near_informants_export')]
    public function itemNearInformantsExport(int $id): Response
    {
        $geoPoint = $this->getGeoPoint($id);
        $radius = LocationService::POINT_NEAR;

        $informantsByLocation = $this->informantService->getInformantsNearGeoPoint($geoPoint, $radius);

        $csv = $this->informantService->generateCsvFromInformants($informantsByLocation);
        $content = $csv->toString();

        $response = new Response($content);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $geoPoint->getId() . '_informants.csv'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
