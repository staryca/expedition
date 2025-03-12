<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Entity\Report;
use App\Repository\ExpeditionRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReportController extends AbstractController
{
    public function __construct(
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportRepository $reportRepository,
        private readonly UserRepository $userRepository,
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

        return $this->render('report/show.html.twig', [
            'report' => $report,
            'mediaFolder' => $this->getParameter('media_folder'),
        ]);
    }

    #[Route('/expedition/{id}/report/new', name: 'report_new_by_expedition')]
    public function newById(int $id): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($id);

        return $this->newByExpedition($expedition);
    }

    #[Route('/report/{id}/edit', name: 'report_edit')]
    public function edit(int $id): Response
    {
        /** @var Report|null $report */
        $report = $this->reportRepository->find($id);
        if (!$report) {
            throw $this->createNotFoundException('The report does not exist');
        }

        return $this->render('report/edit.html.twig', [
            'report' => $report,
            'users' => $this->userRepository->getList(),
        ]);
    }

    #[Route('/report/new', name: 'report_new_for_active_expedition')]
    public function newForActive(): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->findActive();

        return $this->newByExpedition($expedition);
    }

    private function newByExpedition(?Expedition $expedition): Response
    {
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        return $this->render('report/edit.html.twig', [
            'report' => new Report($expedition),
            'users' => $this->userRepository->getList(),
        ]);
    }
}
