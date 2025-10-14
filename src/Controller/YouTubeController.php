<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Additional\Artist;
use App\Entity\Category;
use App\Entity\Dance;
use App\Entity\Improvisation;
use App\Entity\Pack;
use App\Entity\Region;
use App\Entity\Tradition;
use App\Entity\Type\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\DanceRepository;
use App\Repository\ImprovisationRepository;
use App\Repository\PackRepository;
use App\Repository\RegionRepository;
use App\Repository\TraditionRepository;
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

        $queryParams = [
            'id' => 'UCF-ObqklvVQ1mo4OoCTVbFA'
        ];

        $response = $youtube->channels->listChannels('snippet,contentDetails,statistics', $queryParams);
*/
        $queryParams = [
            'forMine' => true,
            'maxResults' => 25,
            'type' => 'video'
        ];
        $list = $youtube->search->listSearch('id,snippet', $queryParams);

        $data = [];
        foreach ($list->getItems() as $item) {
            $data[] = [$item->getSnippet()->getTitle()];
        }

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['title'],
            'data' => $data,
        ]);
    }

    #[Route('/videos/playlists', name: 'app_videos_playlists')]
    public function playlists(): Response
    {
        $data = [];

        $categories = $this->categoryRepository->findAll();
        foreach ($categories as $category) {
            /** @var Category $category */
            if ($category->getPlaylist() === null) {
                continue;
            }

            $data[] = [
                'name' => CategoryType::getManyOrSingleName($category->getId()),
                'id' => $category->getPlaylist(),
            ];
        }

        $dances = $this->danceRepository->findAll();
        foreach ($dances as $dance) {
            /** @var Dance $dance */
            if ($dance->getPlaylist() === null) {
                continue;
            }

            $data[] = [
                'name' => $dance->getName(),
                'id' => $dance->getPlaylist(),
            ];
        }

        $improvisations = $this->improvisationRepository->findAll();
        foreach ($improvisations as $improvisation) {
            /** @var Improvisation $improvisation */
            if ($improvisation->getPlaylist() === null) {
                continue;
            }

            $data[] = [
                'name' => $improvisation->getName(),
                'id' => $improvisation->getPlaylist(),
            ];
        }

        $packs = $this->packRepository->findAll();
        foreach ($packs as $pack) {
            /** @var Pack $pack */
            if ($pack->getPlaylist() === null) {
                continue;
            }

            $data[] = [
                'name' => $pack->getName(),
                'id' => $pack->getPlaylist(),
            ];
        }

        $regions = $this->regionRepository->findAll();
        foreach ($regions as $region) {
            /** @var Region $region */
            if ($region->getPlaylist() === null) {
                continue;
            }

            $data[] = [
                'name' => $region->getName(),
                'id' => $region->getPlaylist(),
            ];
        }

        $traditions = $this->traditionRepository->findAll();
        foreach ($traditions as $tradition) {
            /** @var Tradition $tradition */
            if ($tradition->getPlaylist() === null) {
                continue;
            }

            $data[] = [
                'name' => $tradition->getName(),
                'id' => $tradition->getPlaylist(),
            ];
        }

        $data[] = [
            'name' => Artist::CHILDREN_NAME,
            'id' => Artist::CHILDREN_PLAYLIST,
        ];

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Name', 'On Youtube'],
            'data' => $data,
        ]);
    }
}
