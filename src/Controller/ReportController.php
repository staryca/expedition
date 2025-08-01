<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Entity\Report;
use App\Entity\ReportBlock;
use App\Entity\Type\CategoryType;
use App\Manager\GeoMapManager;
use App\Repository\ExpeditionRepository;
use App\Repository\GeoPointRepository;
use App\Repository\InformantRepository;
use App\Repository\ReportRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReportController extends AbstractController
{
    public function __construct(
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportRepository $reportRepository,
        private readonly UserRepository $userRepository,
        private readonly GeoPointRepository $geoPointRepository,
        private readonly InformantRepository $informantRepository,
        private readonly TagRepository $tagRepository,
        private readonly GeoMapManager $geoMapManager,
    ) {
    }

    #[Route('/report/{id}/show', name: 'report_show')]
    public function show(int $id): Response
    {
        /** @var Report|null $report */
        $report = $this->reportRepository->find($id);
        if (!$report) {
            throw $this->createNotFoundException('The report does not exist');
        }

        $geoMapData = $this->geoMapManager->getGeoMapDataForReport($report);

        return $this->render('report/show.html.twig', [
            'report' => $report,
            'mediaFolder' => $this->getParameter('media_folder'),
            'geoMapData' => $geoMapData,
        ]);
    }

    #[Route('/expedition/{id}/report/new', name: 'report_new_by_expedition')]
    public function newById(int $id): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($id);

        return $this->editReport($expedition);
    }

    #[Route('/report/{id}/edit', name: 'report_edit')]
    #[IsGranted('ROLE_USER', statusCode: 423)]
    public function edit(int $id): Response
    {
        /** @var Report|null $report */
        $report = $this->reportRepository->find($id);
        if (!$report) {
            throw $this->createNotFoundException('The report does not exist');
        }

        return $this->editReport($report->getExpedition(), $report);
    }

    #[Route('/report/new', name: 'report_new_for_active_expedition')]
    #[IsGranted('ROLE_USER', statusCode: 423)]
    public function newForActive(): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->findActive();

        return $this->editReport($expedition);
    }

    private function editReport(?Expedition $expedition, ?Report $report = null): Response
    {
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $baseGeoPoint = $report?->getGeoPoint();

        $informantsAtLocation = $baseGeoPoint ? $this->informantRepository->findByGeoPoint($baseGeoPoint) : [];

        if (!$baseGeoPoint) {
            $baseGeoPoint = $expedition->getGeoPoint();
        }
        $geoPoints = $baseGeoPoint ? $this->geoPointRepository->findNotFarFromPoint($baseGeoPoint) : [];

        $tags = $this->tagRepository->getAllNames();

        return $this->render('report/edit.html.twig', [
            'report' => $report ?? new Report($expedition),
            'newBlock' => new ReportBlock(),
            'users' => $this->userRepository->getList(),
            'categories' => CategoryType::TYPES,
            'geoPoints' => $geoPoints,
            'tags' => $tags,
            'informantsAtLocation' => $informantsAtLocation,
        ]);
    }
}
