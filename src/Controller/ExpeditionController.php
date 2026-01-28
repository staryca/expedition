<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Entity\Type\CategoryType;
use App\Handler\ExpeditionHandler;
use App\Manager\GeoMapManager;
use App\Repository\ExpeditionRepository;
use App\Repository\FileMarkerRepository;
use App\Repository\ReportRepository;
use App\Service\MarkerService;
use App\Service\PlaylistService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExpeditionController extends AbstractController
{
    public function __construct(
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportRepository $reportRepository,
        private readonly ExpeditionHandler $expeditionHandler,
        private readonly MarkerService $markerService,
        private readonly PlaylistService $playlistService,
        private readonly GeoMapManager $geoMapManager,
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

        $geoMapData = $this->geoMapManager->getGeoMapDataForExpedition($expedition);

        $statistics = $this->fileMarkerRepository->getStatistics($expedition);

        return $this->render('expedition/show.html.twig', [
            'expedition' => $expedition,
            'reports' => $reports,
            'geoMapData' => $geoMapData,
            'statistics' => $statistics,
            'categories' => CategoryType::TYPES,
        ]);
    }

    #[Route('/expedition/{id}/map', name: 'expedition_show_map')]
    public function showMap(int $id): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($id);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $geoMapData = $this->geoMapManager->getGeoMapDataForMarkers($expedition);

        return $this->render('expedition/map.html.twig', [
            'expedition' => $expedition,
            'geoMapData' => $geoMapData,
        ]);
    }

    #[Route('/expedition/{id}/tips', name: 'expedition_all_tips', methods: ['GET'])]
    public function tips(int $id): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($id);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $tips = $this->expeditionHandler->getTips($expedition);

        $geoMapData = $this->geoMapManager->getGeoMapDataForExpedition($expedition);

        return $this->render('expedition/tips.html.twig', [
            'expedition' => $expedition,
            'geoMapData' => $geoMapData,
            'tips' => $tips,
        ]);
    }

    #[Route('/expedition/{id}/report', name: 'expedition_report', methods: ['GET'])]
    public function report(int $id): Response
    {
        ini_set("memory_limit", "2G");

        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($id);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $markerGroups = $this->markerService->getGroupedMarkersByExpedition($expedition);

        $geoMapData = $this->geoMapManager->getGeoMapDataForExpedition($expedition);

        return $this->render('expedition/report.html.twig', [
            'expedition' => $expedition,
            'geoMapData' => $geoMapData,
            'markerGroups' => $markerGroups,
            'categories' => CategoryType::getManyNames(false),
        ]);
    }

    #[Route('/expedition/{id}/dances', name: 'expedition_dances', methods: ['GET'])]
    public function dances(int $id): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($id);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $markers = $this->fileMarkerRepository->getMarkersByExpedition($expedition, CategoryType::DANCE);

        $playlists = [];
        foreach ($markers as $marker) {
            $playlists[$marker->getId()] = $this->playlistService->getPlaylists($marker);
        }

        return $this->render('expedition/markers.html.twig', [
            'expedition' => $expedition,
            'markers' => $markers,
            'playlists' => $playlists,
        ]);
    }

    #[Route('/expedition/{id}/category/{category}', name: 'expedition_category', methods: ['GET'])]
    public function category(int $id, int $category): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($id);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $markers = $this->fileMarkerRepository->getMarkersByExpedition($expedition, $category);

        $playlists = [];
        foreach ($markers as $marker) {
            $playlists[$marker->getId()] = $this->playlistService->getPlaylists($marker);
        }

        return $this->render('expedition/markers.html.twig', [
            'expedition' => $expedition,
            'markers' => $markers,
            'playlists' => $playlists,
        ]);
    }

    #[Route('/expedition/{id}/tags', name: 'expedition_tags', methods: ['GET'])]
    public function tags(int $id): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($id);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $markers = $this->fileMarkerRepository->getMarkersByExpedition($expedition);

        $listTags = [];
        foreach ($markers as $marker) {
            $rowTag = $marker->getTagNames();
            $listTag = implode('#', $rowTag);
            $listTags[] = $listTag;
        }

        $listTags = array_unique($listTags);
        sort($listTags);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $listTags,
        ]);
    }

    #[Route('/expedition/{id}/organizations', name: 'expedition_organizations', methods: ['GET'])]
    public function organizations(int $id): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($id);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $organizations = [];
        $markerGroups = [];
        foreach ($expedition->getReports() as $report) {
            foreach ($report->getBlocks() as $block) {
                if ($block->getOrganization()) {
                    $organization = $block->getOrganization();
                    if (!isset($markerGroups[$organization->getId()])) {
                        $organizations[$organization->getId()] = $organization->getName();
                        $markerGroups[$organization->getId()] = [];
                    }
                    $markerGroups[$organization->getId()] = [
                        ...$markerGroups[$organization->getId()],
                        ...$block->getFileMarkers()->toArray(),
                    ];
                }
            }
        }

        $geoMapData = $this->geoMapManager->getGeoMapDataForOrganizations($expedition);

        return $this->render('expedition/organizations.html.twig', [
            'expedition' => $expedition,
            'geoMapData' => $geoMapData,
            'markerGroups' => $markerGroups,
            'organizations' => $organizations,
        ]);
    }
}
