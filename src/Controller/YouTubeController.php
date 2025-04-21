<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Parser\Columns\VideoKozColumns;
use App\Repository\ExpeditionRepository;
use App\Repository\ReportRepository;
use Google_Client;
use Google_Service_YouTube;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class YouTubeController extends AbstractController
{
    private const EXPEDITION_ID = 9; // 9

    public function __construct(
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportRepository $reportRepository,
    ) {
    }

    #[Route('/videos/list', name: 'app_videos_list')]
    public function list(): Response
    {
        $client = new Google_Client();
        $client->setApplicationName("YouTube");
        $client->setAuthConfig($this->getParameter('google_credentials'));

        $youtube = new Google_Service_YouTube($client);
        $queryParams = [
            'forMine' => true,
            'maxResults' => 25,
            'type' => 'video'
        ];
        $list = $youtube->search->listSearch('id,snippet', $queryParams);

        $data = [];
        foreach ($list->getItems() as $item) {
            $data[] = [$item->getId()->getVideoId()];
        }

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['id'],
            'data' => $data,
        ]);
    }
}
