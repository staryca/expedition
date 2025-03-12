<?php

declare(strict_types=1);

namespace App\Controller;

use App\Manager\ReportManager;
use App\Repository\ExpeditionRepository;
use App\Repository\ReportBlockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    public function __construct(
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportManager $reportManager,
        private readonly ReportBlockRepository $reportBlockRepository,
    ) {
    }

    #[Route('/search', name: 'app_search')]
    public function search(Request $request): Response
    {
        $reportBlocks = null;
        $query = $request->query->get('q');
        if (!empty($query)) {
            $reportBlocks = $this->reportBlockRepository->findByQueryIndex($query);
        }

        return $this->render(
            'search/index.html.twig',
            [
                'query' => $query,
                'reportBlocks' => $reportBlocks,
            ]
        );
    }

    #[Route('/search/index', name: 'app_search_index')]
    public function searchIndex(): Response
    {
        set_time_limit(2600);

        $data = [];

        $blocks = 0;
        foreach ($this->expeditionRepository->findAllWithReports() as $expedition) {
            foreach ($expedition->getReports() as $report) {
                foreach ($report->getBlocks() as $block) {
                    $this->reportManager->generateSearchForBlock($block);
                    $blocks++;
                }
            }
        }
        $data['blocks'] = $blocks;

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }
}
