<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Entity\Type\CategoryType;
use App\Repository\ExpeditionRepository;
use App\Repository\FileMarkerRepository;
use App\Repository\ReportRepository;
use App\Service\LocationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExpeditionController extends AbstractController
{
    public function __construct(
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportRepository $reportRepository,
        private readonly LocationService $locationService,
        private readonly FileMarkerRepository $fileMarkerRepository,
    ) {
    }

    #[Route('/', name: 'expedition_list', methods: ['GET'])]
    public function list(): Response
    {
        $expeditions = $this->expeditionRepository->findBy([], ['id' => 'ASC']);

        return $this->render('expedition/list.html.twig', [
            'expeditions' => $expeditions,
        ]);
    }

    #[Route('/expedition/{id}', name: 'expedition_show')]
    public function show(int $id): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($id);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $reports = $this->reportRepository->findByExpedition($expedition);

        $geoMapData = $this->locationService->getGeoMapDataForExpedition($expedition);

        $statistics = $this->fileMarkerRepository->getStatistics($expedition);

        return $this->render('expedition/show.html.twig', [
            'expedition' => $expedition,
            'reports' => $reports,
            'geoMapData' => $geoMapData,
            'statistics' => $statistics,
            'categories' => CategoryType::TYPES,
        ]);
    }
}
