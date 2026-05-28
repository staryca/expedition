<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\Type\CategoryType;
use App\Manager\GeoMapManager;
use App\Repository\DanceRepository;
use App\Repository\FileMarkerRepository;
use App\Repository\GeoPointRepository;
use App\Service\DanceService;
use App\Service\MarkerService;
use App\Service\YoutubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContentController extends AbstractController
{
    public function __construct(
        private readonly FileMarkerRepository $fileMarkerRepository,
        private readonly DanceRepository $danceRepository,
        private readonly GeoPointRepository $geoPointRepository,
        private readonly YoutubeService $youtubeService,
        private readonly DanceService $danceService,
        private readonly MarkerService $markerService,
        private readonly GeoMapManager $geoMapManager,
    ) {
    }

    #[Route('/', name: 'main', methods: ['GET'])]
    public function main(): Response
    {
        $view = $this->getParameter('app_view');
        if ($view === 'expedition') {
            return $this->redirectToRoute('expedition_list');
        }

        return $this->content(true);
    }

    #[Route('/content', name: 'content_lists', methods: ['GET'])]
    public function content(bool $isAll = false): Response
    {
        $statisticsCategory = $this->fileMarkerRepository->getStatisticsByCategory();
        $statisticsDance = $this->fileMarkerRepository->getStatisticsByDance();

        $markers = $this->fileMarkerRepository->getMarkersByPublish(true, 1);
        $marker = current($markers);
        $future = $marker && $isAll ? $this->youtubeService->getTitle($marker) : null;

        return $this->render('content/lists.html.twig', [
            'statisticsCategory' => $statisticsCategory,
            'statisticsDance' => $statisticsDance,
            'categories' => CategoryType::getManyNames(),
            'dances' => $this->danceService->getAllNames(),
            'future' => $future,
        ]);
    }

    #[Route('/content/category/{category}', name: 'content_category', methods: ['GET', 'POST'])]
    public function category(int $category, Request $request): Response
    {
        $markersForFilter = $this->fileMarkerRepository->getMarkersInLocation(null, null, $category);

        $data = $request->request->all();
        $formData = $data['f'] ?? [];
        $filters = $this->markerService->getFilters($markersForFilter, $formData);
        $filters->selectCategory($category);

        $markers = $this->fileMarkerRepository->getMarkersByFilters($filters);

        $geoMapData = $this->geoMapManager->getGeoMapDataForMarkers($markers, 300);

        return $this->render('content/markers.html.twig', [
            'markers' => $markers,
            'filters' => $filters,
            'title' => CategoryType::getManyOrSingleName($category),
            'all' => 'Усе катэгорыі',
            'geoMapData' => $geoMapData,
            'categories' => CategoryType::getSingleNames(),
        ]);
    }

    #[Route('/content/dance/{id}', name: 'content_dance', methods: ['GET', 'POST'])]
    public function dance(int $id, Request $request): Response
    {
        $dance = $this->danceRepository->find($id);
        if (null === $dance) {
            throw $this->createNotFoundException('Dance not found');
        }

        $markersForFilter = $this->fileMarkerRepository->getMarkersInLocation(null, null, null, $dance);

        $data = $request->request->all();
        $formData = $data['f'] ?? [];
        $filters = $this->markerService->getFilters($markersForFilter, $formData);
        $filters->selectDance($id);

        $markers = $this->fileMarkerRepository->getMarkersByFilters($filters);

        $geoMapData = $this->geoMapManager->getGeoMapDataForMarkers($markers, 300);

        return $this->render('content/markers.html.twig', [
            'markers' => $markers,
            'filters' => $filters,
            'title' => CategoryType::getSingleName(CategoryType::DANCE) . ' ' . $dance->getName(),
            'all' => 'Усе танцы',
            'geoMapData' => $geoMapData,
            'categories' => CategoryType::getSingleNames(),
        ]);
    }

    #[Route('/content/marker/{id}', name: 'content_marker', methods: ['GET'])]
    public function marker(int $id): Response
    {
        $marker = $this->fileMarkerRepository->find($id);
        if (!$marker) {
            throw $this->createNotFoundException('The marker does not exist');
        }

        $description = $this->youtubeService->getMarkerDescription($marker);

        return $this->render('content/marker.html.twig', [
            'marker' => $marker,
            'category' => CategoryType::getManyOrSingleName($marker->getCategory()),
            'description' => $description,
            'tmkb' => $marker->getAdditionalValue(FileMarkerAdditional::TMKB),
        ]);
    }

    #[Route('/content/future', name: 'content_future', methods: ['GET'])]
    public function future(): Response
    {
        $amount = 100;
        $markers = $this->fileMarkerRepository->getMarkersByPublish(true, $amount);

        $titles = [];
        $descriptions = [];
        foreach ($markers as $key => $marker) {
            $titles[$key] = $this->youtubeService->getTitle($marker);
            $descriptions[$key] = $this->youtubeService->getDescription($marker, false);
        }

        return $this->render('content/future.html.twig', [
            'markers' => $markers,
            'titles' => $titles,
            'descriptions' => $descriptions,
            'isAll' => count($markers) !== $amount,
        ]);
    }

    #[Route('/content/map', name: 'content_map', methods: ['GET'])]
    public function map(): Response
    {
        $markers = $this->fileMarkerRepository->getAllWithFullObjects();
        $geoMapData = $this->geoMapManager->getGeoMapDataForMarkers($markers, 900, true);

        return $this->render('content/map.html.twig', [
            'geoMapData' => $geoMapData,
        ]);
    }

    #[Route('/content/place/{id}', name: 'content_place', methods: ['GET'])]
    public function place(int $id): Response
    {
        $geoPoint = $this->geoPointRepository->find($id);
        if (!$geoPoint) {
            throw $this->createNotFoundException('The place does not exist');
        }

        $markerGroups = $this->markerService->getGroupedMarkersByGeoPoint($geoPoint);

        return $this->render('content/place.html.twig', [
            'geoPoint' => $geoPoint,
            'reports' => [],
            'informants' => [],
            'organizations' => [],
            'subjects' => [],
            'tasks' => [],
            'markerGroups' => $markerGroups,
            'categories' => CategoryType::getManyNames(false),
        ]);
    }
}
