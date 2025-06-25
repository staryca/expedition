<?php

declare(strict_types=1);

namespace App\Controller;

use App\Handler\SearchHandler;
use App\Repository\ReportBlockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    public function __construct(
        private readonly SearchHandler $searchHandler,
        private readonly ReportBlockRepository $reportBlockRepository,
    ) {
    }

    #[Route('/search', name: 'app_search')]
    public function search(Request $request): Response
    {
        set_time_limit(120);
        ini_set("memory_limit", "512M");

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
        ini_set("memory_limit", "16G");

        $data = [];

        $blocks = 0;
        foreach ($this->reportBlockRepository->findNotIndexed() as $block) {
            $this->searchHandler->generateSearchForBlock($block);
            $data['ids'][] = $block->getId();
            $blocks++;
        }
        $data['blocks'] = $blocks;

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }
}
