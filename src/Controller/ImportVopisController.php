<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Manager\ReportManager;
use App\Parser\VopisParser;
use App\Repository\ExpeditionRepository;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportVopisController extends AbstractController
{
    private const EXPEDITION_ID = 100;
    private const DATE_CREATED = '2013-02-20';
    private const WITH_TIME = false;

    public function __construct(
        private readonly VopisParser $parser,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportManager $reportManager,
    ) {
    }

    #[Route('/import/vopis/check', name: 'app_import_vopis_check')]
    public function check(): Response
    {
        $data = [];

        $filename = '../var/data/vopis/vopis.csv';
        $content = file_get_contents($filename);
        $files = $this->parser->parse($content, self::WITH_TIME);
        $data['files_count'] = count($files);

        $reports = $this->parser->createReports($files, Carbon::parse(self::DATE_CREATED));
        $data['reports_count'] = count($reports);

        $data['reports_location_errors'] = [];
        foreach ($reports as $report) {
            if (null !== $report->geoNotes) {
                $data['reports_location_errors'][] = $report;
            }
        }

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/vopis/save', name: 'app_import_vopis_save')]
    public function save(): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        $data = [];

        $filename = '../var/data/vopis/vopis.csv';
        $content = file_get_contents($filename);
        $files = $this->parser->parse($content, self::WITH_TIME);
        $data['files_count'] = count($files);

        $reports = $this->parser->createReports($files, Carbon::parse(self::DATE_CREATED));

        $this->reportManager->saveVopisReports(
            $expedition,
            $reports,
            $files,
        );

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }
}
