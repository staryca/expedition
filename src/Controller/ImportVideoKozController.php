<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Entity\FileMarker;
use App\Entity\Type\GenderType;
use App\Handler\VideoKozHandler;
use App\Parser\Columns\VideoKozColumns;
use App\Repository\ExpeditionRepository;
use App\Repository\FileMarkerRepository;
use App\Repository\ReportRepository;
use App\Service\YoutubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportVideoKozController extends AbstractController
{
    private const EXPEDITION_ID = 990; // 9
    private const FILENAME = '../var/data/video_koz/br-004.csv';

    public function __construct(
        private readonly VideoKozHandler $videoKozHandler,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportRepository $reportRepository,
        private readonly FileMarkerRepository $fileMarkerRepository,
        private readonly YoutubeService $youtubeService,
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

        $markers = $this->fileMarkerRepository->getMarkersWithFullObjects($expedition);
        $result = ['all' => 0, 'videos' => 0, 'no_found' => 0, 'updated' => 0, 'items' => []];
        foreach ($markers as $fileMarker) {
            $result['all']++;
            $additional = $fileMarker->getAdditional();
            $videoId = $additional['youtube'] ?? null;
            if ($videoId) {
                $result['videos']++;
                $response = $this->youtubeService->updateInYouTube($fileMarker);
                if (is_string($response)) {
                    $result['no_found']++;
                } elseif (null !== $response) {
                    $result['updated']++;
                }
                $result['items'][$videoId] = $response;
            }
        }

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

        $response = $this->youtubeService->updateInYouTube($fileMarker);
        if (is_string($response)) {
            $data = ['error' => $response];
            $data['id'] = $fileMarker->getAdditionalYoutube();
        } else {
            $data = get_object_vars($response);
            $data['link'] = $fileMarker->getAdditionalYoutubeLink();
        }

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }
}
