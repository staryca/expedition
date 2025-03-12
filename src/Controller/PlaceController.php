<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LocationGroupDto;
use App\Entity\GeoPoint;
use App\Repository\GeoPointRepository;
use App\Repository\InformantRepository;
use App\Repository\OrganizationRepository;
use App\Repository\ReportRepository;
use App\Repository\SubjectRepository;
use App\Repository\TagRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/place/item/{id}', name: 'place_item')]
    public function item(int $id): Response
    {
        /** @var GeoPoint|null $geoPoint */
        $geoPoint = $this->geoPointRepository->find($id);
        if (!$geoPoint) {
            throw $this->createNotFoundException('The GeoPoint does not exist');
        }

        $reports = $this->reportRepository->findByGeoPoint($geoPoint);

        $informants = $this->informantRepository->findByGeoPoint($geoPoint);

        $organizations = $this->organizationRepository->findByGeoPoint($geoPoint);

        $subjects = $this->subjectRepository->findByGeoPoint($geoPoint);

        $tasks = $this->taskRepository->findByGeoPoint($geoPoint);

        return $this->render('place/item.show.html.twig', [
            'geoPoint' => $geoPoint,
            'reports' => $reports,
            'informants' => $informants,
            'organizations' => $organizations,
            'subjects' => $subjects,
            'tasks' => $tasks,
        ]);
    }
}
