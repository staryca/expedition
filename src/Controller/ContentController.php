<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\Type\CategoryType;
use App\Manager\GeoMapManager;
use App\Repository\DanceRepository;
use App\Repository\FileMarkerRepository;
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
        private readonly YoutubeService $youtubeService,
        private readonly DanceService $danceService,
        private readonly MarkerService $markerService,
        private readonly GeoMapManager $geoMapManager,
    ) {
    }

    #[Route('/content', name: 'content_lists', methods: ['GET'])]
    public function content(): Response
    {
        $statisticsCategory = $this->fileMarkerRepository->getStatisticsByCategory();
        $statisticsDance = $this->fileMarkerRepository->getStatisticsByDance();

        $markers = $this->fileMarkerRepository->getMarkersByPublish(true, 1);
        $marker = current($markers);
        $future = $marker ? $this->youtubeService->getTitle($marker) : null;

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
        $markers = $this->fileMarkerRepository->getMarkersInLocation(null, null, $category);

        $data = $request->request->all();
        $formData = $data['f'] ?? [];
        $filters = $this->markerService->getFilters($markers, $formData);
        $filters->selectCategory($category);

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

        $markers = $this->fileMarkerRepository->getMarkersInLocation(null, null, null, $dance);

        $data = $request->request->all();
        $formData = $data['f'] ?? [];
        $filters = $this->markerService->getFilters($markers, $formData);
        $filters->selectDance($id);

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
}
