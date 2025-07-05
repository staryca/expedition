<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Entity\Type\GenderType;
use App\Handler\VideoKozHandler;
use App\Parser\Columns\VideoKozColumns;
use App\Repository\ExpeditionRepository;
use App\Repository\ReportRepository;
use App\Service\YoutubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportVideoKozController extends AbstractController
{
    private const EXPEDITION_ID = 182; // 9
    private const FILENAME = '../var/data/video_koz/br-o.csv';

    public function __construct(
        private readonly VideoKozHandler $videoKozHandler,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportRepository $reportRepository,
        private readonly YoutubeService $youtubeService,
    ) {
    }

    #[Route('/import/video_koz/check', name: 'app_import_video_koz_check')]
    public function check(): Response
    {
        try {
            $files = $this->videoKozHandler->checkFile(self::FILENAME);
        } catch (\Exception $exception) {
            throw $exception;
        }

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
                    $item['youtube_title'] = $this->youtubeService->getTitle($report, $fileMarker);
                    $item['youtube_description'] = $this->youtubeService->getDescription($report, $block, $fileMarker);
//                    $item['category'] = $fileMarker->getCategoryName();
//                    $item['name'] = $fileMarker->getAdditionalValue('baseName');
//                    $item['name_local'] = $fileMarker->getAdditionalValue('localName');
//                    $item['dance_type'] = $fileMarker->getAdditionalValue('danceType');
//                    $item['improvisation'] = $fileMarker->getAdditionalValue('improvisation');
//                    $item['ritual'] = $fileMarker->getAdditionalValue('ritual');
//                    $item['tradition'] = $fileMarker->getAdditionalValue('tradition');
//                    $item['location'] = $report->getGeoPlace();
//                    $item['date'] = $report->getTextDateAction();
//                    $item['description'] = $fileMarker->getNotes();
//                    $item['org'] = $block->getOrganization()?->getName();
//                    $item['informants'] = $block->getInformants()->count() . ' persons';
//
//                    $text = (string) $fileMarker->getDecoding();
//                    $item['texts'] = mb_strlen($text) > 100 ? mb_substr($text, 0, 100) . '...' : $text;
//                    $item['tmkb'] = $fileMarker->getAdditionalValue('tmkb');
//                    $item['additional'] = var_export($fileMarker->getAdditional(), true);

                    $data[] = $item;
                }
            }
        }

        return $this->render('import/show.table.result.html.twig', [
            'headers' => [
                VideoKozColumns::FILENAME,
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
        ]);
    }
}
