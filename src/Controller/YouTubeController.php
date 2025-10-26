<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Additional\Artist;
use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\Category;
use App\Entity\Dance;
use App\Entity\Improvisation;
use App\Entity\Pack;
use App\Entity\Region;
use App\Entity\Tradition;
use App\Entity\Type\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\DanceRepository;
use App\Repository\ExpeditionRepository;
use App\Repository\FileMarkerRepository;
use App\Repository\ImprovisationRepository;
use App\Repository\PackRepository;
use App\Repository\RegionRepository;
use App\Repository\TraditionRepository;
use App\Service\MarkerService;
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
        private readonly FileMarkerRepository $fileMarkerRepository,
        private readonly MarkerService $markerService,
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

        $expedition = $this->expeditionRepository->find(992);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }
        $markerGroups = $this->markerService->getGroupedMarkersByExpedition($expedition);


        $categories = $this->categoryRepository->findAll();
        foreach ($categories as $category) {
            /** @var Category $category */
            if (CategoryType::isSystemType($category->getId()) || !CategoryType::isImportantType($category->getId())) {
                continue;
            }

            $data[] = [
                'name' => 'C) ' . CategoryType::getManyOrSingleName($category->getId()),
                'count' => isset($markerGroups[$category->getId()]) ? count($markerGroups[$category->getId()]) : 0,
                'id' => $category->getPlaylist(),
            ];
        }


        $markers = $markerGroups[CategoryType::DANCE] ?? [];
        $counts = [];
        foreach ($markers as $marker) {
            $additional = $marker->getAdditional();
            $dance = $additional[FileMarkerAdditional::BASE_NAME] ?? null;
            if ($dance) {
                $counts[$dance] = isset($counts[$dance]) ? $counts[$dance] + 1 : 1;
            }
        }

        $dances = $this->danceRepository->findAll();
        foreach ($dances as $dance) {
            $count = $counts[$dance->getName()] ?? 0;
            /** @var Dance $dance */
            if ($count === 0) {
                continue;
            }

            $data[] = [
                'name' => 'D) ' . CategoryType::getSingleName(CategoryType::DANCE) . ' ' . $dance->getName(),
                'count' => $count,
                'id' => $dance->getPlaylist(),
            ];
            unset($counts[$dance->getName()]);
        }
        foreach ($counts as $name => $count) {
            $data[] = [
                'name' => 'DE) ' . CategoryType::getSingleName(CategoryType::DANCE) . ' ' . $name,
                'count' => $count,
                'id' => '',
            ];
        }


        $counts = [];
        foreach ($markers as $marker) {
            $additional = $marker->getAdditional();
            $improvisation = $additional[FileMarkerAdditional::IMPROVISATION] ?? null;
            if ($improvisation) {
                $counts[$improvisation] = isset($counts[$improvisation]) ? $counts[$improvisation] + 1 : 1;
            }
        }

        $improvisations = $this->improvisationRepository->findAll();
        foreach ($improvisations as $improvisation) {
            /** @var Improvisation $improvisation */
            $count = $counts[$improvisation->getName()] ?? 0;
            if ($count === 0) {
                continue;
            }

            $data[] = [
                'name' => 'I) ' . CategoryType::getSingleName(CategoryType::DANCE) . ' ' . $improvisation->getName(),
                'count' => $count,
                'id' => $improvisation->getPlaylist(),
            ];
            unset($counts[$improvisation->getName()]);
        }
        foreach ($counts as $name => $count) {
            $data[] = [
                'name' => 'IE) ' . CategoryType::getSingleName(CategoryType::DANCE) . ' ' . $name,
                'count' => $count,
                'id' => '',
            ];
        }


        $counts = [];
        $countsChildren = 0;
        foreach ($markerGroups as $markers) {
            foreach ($markers as $marker) {
                $additional = $marker->getAdditional();
                $danceType = $additional[FileMarkerAdditional::DANCE_TYPE] ?? null;
                if ($danceType) {
                    $counts[$danceType] = isset($counts[$danceType]) ? $counts[$danceType] + 1 : 1;
                }

                $children = $additional[FileMarkerAdditional::SOURCE] ?? null;
                if (Artist::isChildren($children)) {
                    $countsChildren++;
                }
            }
        }

        $packs = $this->packRepository->findAll();
        foreach ($packs as $pack) {
            /** @var Pack $pack */
            $count = $counts[$pack->getName()] ?? 0;
            if ($count === 0) {
                continue;
            }

            $data[] = [
                'name' => 'T) ' . CategoryType::getSingleName(CategoryType::DANCE) . ' ' . $pack->getName(),
                'count' => $count,
                'id' => $pack->getPlaylist(),
            ];
            unset($counts[$pack->getName()]);
        }
        foreach ($counts as $name => $count) {
            $data[] = [
                'name' => 'TE) ' . CategoryType::getSingleName(CategoryType::DANCE) . ' ' . $name,
                'count' => $count,
                'id' => '',
            ];
        }


        $regions = $this->regionRepository->findAll();
        foreach ($regions as $region) {
            /** @var Region $region */
            if ($region->getPlaylist() === null) {
                continue;
            }

            $data[] = [
                'name' => 'R) ' . $region->getName(),
                'count' => 0,
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
                'name' => 'A) ' . $tradition->getName(),
                'count' => 0,
                'id' => $tradition->getPlaylist(),
            ];
        }


        $data[] = [
            'name' => 'S) ' . Artist::CHILDREN_NAME,
            'count' => $countsChildren,
            'id' => Artist::CHILDREN_PLAYLIST,
        ];

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Name', 'Count', 'On Youtube'],
            'data' => $data,
        ]);
    }
}
