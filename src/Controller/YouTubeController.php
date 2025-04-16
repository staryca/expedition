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

    #[Route('/videos/kozienka', name: 'app_videos_kozienka')]
    public function kozienka(): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $reports = $this->reportRepository->findByExpedition($expedition);

        $data = [];
        foreach ($reports as $report) {
            foreach ($report->getBlocks() as $block) {
                foreach ($block->getFileMarkers() as $fileMarker) {
                    $item = [];
                    $item['file'] = $fileMarker->getFile()?->getFullFileName();
                    $item['category'] = $fileMarker->getCategoryName();
                    $item['name'] = $fileMarker->getName();
                    $item['name_local'] = $fileMarker->getName();
                    $item['dance_type'] = $fileMarker->getAdditional()['danceType'] ?? '';
                    $item['improvisation'] = $fileMarker->getAdditional()['improvisation'] ?? '';
                    $item['ritual'] = $fileMarker->getAdditional()['ritual'] ?? '';
                    $item['location'] = $report->getGeoPlace();
                    $item['date'] = $report->getTextDateAction();
                    $item['description'] = $fileMarker->getNotes();
                    $item['org'] = $block->getOrganization()?->getName();
                    $item['informants'] = $block->getInformants()->count() . ' persons';
                    $item['texts'] = $fileMarker->getDecoding();
                    $item['tmkb'] = $fileMarker->getAdditional()['tmkb'] ?? '';

                    $data[] = $item;
                }
            }
        }

        return $this->render('import/show.table.result.html.twig', [
            'headers' => [
                VideoKozColumns::FILENAME,
                VideoKozColumns::TYPE_RECORD,
                VideoKozColumns::BASE_NAME,
                VideoKozColumns::LOCAL_NAME,
                VideoKozColumns::TYPE_DANCE,
                VideoKozColumns::IMPROVISATION,
                VideoKozColumns::RITUAL,
                VideoKozColumns::VILLAGE,
                VideoKozColumns::DATE_RECORD,
                VideoKozColumns::DESCRIPTION,
                VideoKozColumns::ORGANIZATION,
                VideoKozColumns::INFORMANTS,
                VideoKozColumns::TEXTS,
                VideoKozColumns::TMKB,
            ],
            'data' => $data,
        ]);
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
