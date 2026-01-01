<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PlaylistInfoDto;
use App\Entity\Additional\Artist;
use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\Dance;
use App\Entity\Improvisation;
use App\Entity\Pack;
use App\Entity\Region;
use App\Entity\Ritual;
use App\Entity\Tradition;
use App\Entity\Type\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\DanceRepository;
use App\Repository\ExpeditionRepository;
use App\Repository\FileMarkerRepository;
use App\Repository\ImprovisationRepository;
use App\Repository\PackRepository;
use App\Repository\RegionRepository;
use App\Repository\RitualRepository;
use App\Repository\TraditionRepository;
use App\Service\MarkerService;
use App\Service\PlaylistService;
use App\Service\YoutubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class YouTubeController extends AbstractController
{
    public function __construct(
        private readonly YoutubeService $youtubeService,
        private readonly CategoryRepository $categoryRepository,
        private readonly DanceRepository $danceRepository,
        private readonly ImprovisationRepository $improvisationRepository,
        private readonly PackRepository $packRepository,
        private readonly RegionRepository $regionRepository,
        private readonly TraditionRepository $traditionRepository,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly RitualRepository $ritualRepository,
        private readonly FileMarkerRepository $fileMarkerRepository,
        private readonly MarkerService $markerService,
        private readonly PlaylistService $playlistService,
    ) {
    }

    #[Route('/videos/list', name: 'app_videos_list')]
    public function list(): Response
    {
        $youtube = $this->youtubeService->getYoutubeService();

        // by playlist (with api_key)
        //$list = $youtube->playlistItems->listPlaylistItems('snippet', ['playlistId' => 'PLcZbXijhBgIoxkJvHt_lbm9fp72mCjcvX']);

/*
        $service = new Google_Service_YouTube($client);
*/
        $queryParams = [
            'id' => 'UCF-ObqklvVQ1mo4OoCTVbFA'
        ];

        $list = $youtube->channels->listChannels('snippet,contentDetails,statistics', $queryParams);
/*
        $queryParams = [
            'forMine' => true,
            'maxResults' => 25,
            'type' => 'video'
        ];
        $list = $youtube->search->listSearch('id,snippet', $queryParams);
*/
        $data = [];
        foreach ($list->getItems() as $item) {
            $data[] = [$item->getSnippet()->getTitle()];
        }

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['title'],
            'data' => $data,
        ]);
    }
}
