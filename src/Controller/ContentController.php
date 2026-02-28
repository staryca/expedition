<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\Type\CategoryType;
use App\Repository\FileMarkerRepository;
use App\Service\YoutubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContentController extends AbstractController
{
    public function __construct(
        private readonly FileMarkerRepository $fileMarkerRepository,
        private readonly YoutubeService $youtubeService,
    ) {
    }

    #[Route('/content', name: 'content_lists', methods: ['GET'])]
    public function content(): Response
    {
        $statistics = $this->fileMarkerRepository->getStatistics();

        $markers = $this->fileMarkerRepository->getMarkersByPublish(true, 1);
        $marker = current($markers);
        $future = $marker ? $this->youtubeService->getTitle($marker) : null;

        return $this->render('content/lists.html.twig', [
            'statistics' => $statistics,
            'categories' => CategoryType::getManyNames(),
            'future' => $future,
        ]);
    }

    #[Route('/content/category/{category}', name: 'content_category', methods: ['GET'])]
    public function category(int $category): Response
    {
        $markers = $this->fileMarkerRepository->getMarkersInLocation(null, null, $category);

        return $this->render('content/markers.html.twig', [
            'markers' => $markers,
            'category' => CategoryType::getManyOrSingleName($category),
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
        foreach ($markers as $key => $marker) {
            $titles[$key] = $this->youtubeService->getTitle($marker);
        }

        return $this->render('content/future.html.twig', [
            'markers' => $markers,
            'titles' => $titles,
            'isAll' => count($markers) !== $amount,
        ]);
    }
}
