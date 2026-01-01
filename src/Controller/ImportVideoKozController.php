<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PlaylistInfoDto;
use App\Entity\Additional\Artist;
use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\Category;
use App\Entity\Dance;
use App\Entity\Expedition;
use App\Entity\FileMarker;
use App\Entity\Improvisation;
use App\Entity\Pack;
use App\Entity\Region;
use App\Entity\Ritual;
use App\Entity\Tradition;
use App\Entity\Type\CategoryType;
use App\Entity\Type\GenderType;
use App\Handler\VideoKozHandler;
use App\Parser\Columns\VideoKozColumns;
use App\Repository\CategoryRepository;
use App\Repository\DanceRepository;
use App\Repository\ExpeditionRepository;
use App\Repository\FileMarkerRepository;
use App\Repository\ImprovisationRepository;
use App\Repository\PackRepository;
use App\Repository\RegionRepository;
use App\Repository\ReportRepository;
use App\Repository\RitualRepository;
use App\Repository\TraditionRepository;
use App\Service\MarkerService;
use App\Service\PlaylistService;
use App\Service\YoutubeService;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportVideoKozController extends AbstractController
{
    private const EXPEDITION_ID = 994; // 9
    private const FILENAME = '../var/data/video_koz/br-07.csv';

    public function __construct(
        private readonly VideoKozHandler $videoKozHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportRepository $reportRepository,
        private readonly FileMarkerRepository $fileMarkerRepository,
        private readonly YoutubeService $youtubeService,
        private readonly CategoryRepository $categoryRepository,
        private readonly DanceRepository $danceRepository,
        private readonly ImprovisationRepository $improvisationRepository,
        private readonly PackRepository $packRepository,
        private readonly RegionRepository $regionRepository,
        private readonly TraditionRepository $traditionRepository,
        private readonly RitualRepository $ritualRepository,
        private readonly MarkerService $markerService,
        private readonly PlaylistService $playlistService,
    ) {
    }

    #[Route('/import/video_koz/check', name: 'app_import_video_koz_check')]
    public function check(): Response
    {
        $files = $this->videoKozHandler->checkFile(self::FILENAME);

        $data = [];
        $data['errors_type'] = [];
        $data['errors_location'] = [];
        $data['errors_date'] = [];
        $data['date_notes'] = [];
        foreach ($files as $file) {
            foreach ($file->videoItems as $videoItem) {
                if ($videoItem->category === null) {
                    $data['errors_type'][] = $videoItem;
                }
                if (null === $videoItem->geoPoint) {
                    $data['errors_location'][] = $videoItem;
                }
                if (null === $videoItem->dateAction && empty($videoItem->dateActionNotes)) {
                    $data['errors_date'][] = $videoItem;
                }
                if (!empty($videoItem->dateActionNotes)) {
                    $data['date_notes'][] = $videoItem->dateActionNotes;
                }
            }
        }

        $informants = $this->videoKozHandler->getInformants($files);
        $data['informants'] = $informants;

        $data['errors_gender'] = [];
        foreach ($informants as $informant) {
            if ($informant->gender === GenderType::UNKNOWN) {
                $data['errors_gender'][] = $informant;
            }
        }

        $organizations = $this->videoKozHandler->getOrganizations($files);
        $data['orgs'] = $organizations;
        $newInformants = [];
        foreach ($organizations as $organization) {
            foreach ($organization->informants as $informant) {
                $newInformants[] = $informant;
            }
        }
        $data['newInformants_must_empty'] = $newInformants;

        $reportsData = $this->videoKozHandler->createReportsData($files);
        $data['reports'] = $reportsData;

        $this->videoKozHandler->convertVideoItemsToFileMarkers($files);
        $data['files'] = $files;

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/video_koz/save', name: 'app_import_video_koz_save')]
    public function save(): Response
    {
        try {
            $files = $this->videoKozHandler->checkFile(self::FILENAME);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }

        $reports = $this->videoKozHandler->saveFiles(self::EXPEDITION_ID, $files);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $reports,
        ]);
    }

    #[Route('/import/video_koz/list', name: 'app_import_video_koz_list')]
    public function list(): Response
    {
        try {
            $data = $this->videoKozHandler->getYoutubeList(self::EXPEDITION_ID);
        } catch (\Exception $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        return $this->render('import/show.table.result.html.twig', [
            'headers' => [
                'Actions',
                'Status',
                VideoKozColumns::FILENAME,
                'Youtube',
                'Youtube title',
                'Youtube description',
//                VideoKozColumns::TYPE_RECORD,
//                VideoKozColumns::BASE_NAME,
//                VideoKozColumns::LOCAL_NAME,
//                VideoKozColumns::TYPE_DANCE,
//                VideoKozColumns::IMPROVISATION,
//                VideoKozColumns::RITUAL,
//                VideoKozColumns::TRADITION,
//                VideoKozColumns::VILLAGE,
//                VideoKozColumns::DATE_RECORD,
//                VideoKozColumns::DESCRIPTION,
//                VideoKozColumns::ORGANIZATION,
//                VideoKozColumns::INFORMANTS,
//                VideoKozColumns::TEXTS,
//                VideoKozColumns::TMKB,
//                'Additional',
            ],
            'data' => $data,
            'actions' => [
                'app_import_video_koz_update_item' => 'bi-arrow-clockwise',
                'app_import_video_koz_show_item' => 'bi-eye-fill',
            ],
        ]);
    }

    #[Route('/import/video_koz/update/all', name: 'app_import_video_koz_update_all_items')]
    public function updateAllItems(): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $markers = $this->fileMarkerRepository->getMarkersWithFullObjects($expedition, [FileMarkerAdditional::STATUS_UPDATED => false]);
        $result = ['message' => 'ok', 'all' => 0, 'videos' => 0, 'no_found' => 0, 'updated' => 0, 'items' => []];
        foreach ($markers as $fileMarker) {
            $result['all']++;
            $additional = $fileMarker->getAdditional();
            $videoId = $additional['youtube'] ?? null;
            if (empty($videoId)) {
                continue;
            }

            try {
                $response = $this->youtubeService->updateInYouTube($fileMarker);
            } catch (Exception | \Google\Exception $e) {
                $result['message'] = $e->getMessage();
                break;
            }

            if (is_string($response) || null === $response) {
                if (str_contains($response, 'No videos found.')) {
                    $result['no_found']++;
                } else {
                    $result['message'] = $response;
                    break;
                }
            } else {
                $result['updated']++;
            }
            $result['videos']++;
            $result['items'][$videoId] = $response;
        }

        $this->entityManager->flush();

        return $this->render('import/show.json.result.html.twig', [
            'data' => $result,
        ]);
    }

    #[Route('/import/video_koz/show/all', name: 'app_import_video_koz_show_all_items')]
    public function showAllItems(): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $markers = $this->fileMarkerRepository->getMarkersWithFullObjects($expedition, [FileMarkerAdditional::STATUS_ACTIVE => false]);
        $result = ['message' => 'ok', 'all' => 0, 'videos' => 0, 'no_found' => 0, 'showed' => 0, 'items' => []];
        foreach ($markers as $fileMarker) {
            $additional = $fileMarker->getAdditional();
            $videoId = $additional['youtube'] ?? null;
            if ($videoId) {
                try {
                    $response = $this->youtubeService->showInYouTube($fileMarker);
                } catch (Exception | \Google\Exception $e) {
                    $result['message'] = $e->getMessage();
                    break;
                }

                $result['videos']++;
                if (is_string($response)) {
                    $result['no_found']++;
                } elseif (null !== $response) {
                    $result['showed']++;
                }
                $result['items'][$videoId] = $response;
            }
            $result['all']++;
        }

        $this->entityManager->flush();

        return $this->render('import/show.json.result.html.twig', [
            'data' => $result,
        ]);
    }

    #[Route('/import/video_koz/update/{id}', name: 'app_import_video_koz_update_item')]
    public function updateItem(int $id): Response
    {
        /** @var FileMarker|null $fileMarker */
        $fileMarker = $this->fileMarkerRepository->find($id);
        if (!$fileMarker) {
            throw $this->createNotFoundException('The fileMarker does not exist');
        }

        $data = [];
        $data['id'] = $fileMarker->getAdditionalYoutube();
        try {
            $response = $this->youtubeService->updateInYouTube($fileMarker);
        } catch (Exception | \Google\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        if (isset($response)) {
            if (is_string($response)) {
                $data = ['error' => $response];
            } else {
                $data = get_object_vars($response);
                $data['link'] = $fileMarker->getAdditionalYoutubeLink();
            }

            $this->entityManager->flush();
        }

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/video_koz/show/{id}', name: 'app_import_video_koz_show_item')]
    public function showItem(int $id): Response
    {
        /** @var FileMarker|null $fileMarker */
        $fileMarker = $this->fileMarkerRepository->find($id);
        if (!$fileMarker) {
            throw $this->createNotFoundException('The fileMarker does not exist');
        }

        $data = [];
        $data['id'] = $fileMarker->getAdditionalYoutube();
        try {
            $response = $this->youtubeService->showInYouTube($fileMarker);
        } catch (Exception | \Google\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        if (isset($response)) {
            if (is_string($response)) {
                $data = ['error' => $response];
            } else {
                $data = get_object_vars($response);
                $data['link'] = $fileMarker->getAdditionalYoutubeLink();
            }

            $this->entityManager->flush();
        }

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/video_koz/playlists', name: 'app_import_video_koz_playlists')]
    public function playlists(): Response
    {
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }
        $markerGroups = $this->markerService->getGroupedMarkersByExpedition($expedition);

        $data = [];

        // Categories
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

        // Dances
        $danceMarkers = $markerGroups[CategoryType::DANCE] ?? [];
        $counts = [];
        foreach ($danceMarkers as $marker) {
            $dance = $marker->getAdditionalDance();
            if (!empty($dance)) {
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

        // Improvisations
        $counts = [];
        foreach ($danceMarkers as $marker) {
            $improvisation = $marker->getAdditionalImprovisation();
            if (!empty($improvisation)) {
                $counts[$improvisation] = isset($counts[$improvisation]) ? $counts[$improvisation] + 1 : 1;
            }
        }

        $improvisations = $this->improvisationRepository->findAll();
        foreach ($improvisations as $improvisation) {
            /** @var Improvisation $improvisation */
            $count = $counts[$improvisation->getName()] ?? 0;

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

        // Packs
        $counts = [];
        $countsChildren = 0;
        foreach ($markerGroups as $markers) {
            foreach ($markers as $marker) {
                $additional = $marker->getAdditional();
                $danceType = $marker->getAdditionalPack();
                if (!empty($danceType)) {
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

        // Regions
        $regions = $this->regionRepository->findAll();
        foreach ($regions as $region) {
            /** @var Region $region */
            if ($region->getPlaylist() === null) {
                continue;
            }

            $data[] = [
                'name' => 'G) ' . $region->getName(),
                'count' => 0,
                'id' => $region->getPlaylist(),
            ];
        }

        // Traditions
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

        // Rituals
        $counts = [];
        foreach ($markerGroups as $markers) {
            foreach ($markers as $marker) {
                if ($marker->getRitual()) {
                    $id = $marker->getRitual()->getId();
                    $counts[$id] = isset($counts[$id]) ? $counts[$id] + 1 : 1;
                }
            }
        }

        $rituals = $this->ritualRepository->findAll();
        foreach ($rituals as $ritual) {
            /** @var Ritual $ritual */
            if (!isset($counts[$ritual->getId()])) {
                continue;
            }

            $data[] = [
                'name' => 'R) ' . $ritual->getName(),
                'count' => $counts[$ritual->getId()],
                'id' => $ritual->getPlaylist(),
            ];
            unset($counts[$ritual->getId()]);
        }

        // Children
        $data[] = [
            'name' => 'S) ' . Artist::CHILDREN_NAME,
            'count' => $countsChildren,
            'id' => Artist::CHILDREN_PLAYLIST,
        ];

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Name', 'Count', 'On Youtube'],
            'data' => $data,
            'actions' => [
                'app_import_video_koz_category_update' => 'bi-arrow-clockwise',
            ],
        ]);
    }

    #[Route('/import/video_koz/category/{id}', name: 'app_import_video_koz_category_update')]
    public function categoryUpdate(int $id): Response
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            throw $this->createNotFoundException('The expedition does not exist');
        }

        $markers = $this->fileMarkerRepository->getMarkersByExpedition($expedition, $id);
        $playlist = $category->getPlaylist();

        $data = $this->youtubeService->addMarkersInPlaylist($playlist, $markers);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }
}
