<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Entity\Type\ReportBlockType;
use App\Manager\ReportManager;
use App\Parser\KoboParser;
use App\Repository\ExpeditionRepository;
use App\Service\LocationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportKoboController extends AbstractController
{
    private const EXPEDITION_ID = 355;

    public function __construct(
        private readonly KoboParser $parser,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly LocationService $locationService,
        private readonly ReportManager $reportManager,
    ) {
    }

    private function getExpedition(): Expedition
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        return $expedition;
    }

    #[Route('/import/kobo/check', name: 'app_import_kobo_check')]
    public function check(): Response
    {
        $data = [];

        $filename = '../var/data/kobo/reports.csv';
        $content = file_get_contents($filename);
        $reports = $this->parser->parseReports($content);
        $data['reports_count'] = count($reports);

        $data['not_detected_locations'] = [];
        $data['no_users'] = [];
        $data['report_users_errors'] = [];
        $data['report_block_no_additional'] = [];
        $data['warn_report_block_no_type'] = [];
        $data['error_users_not_found'] = [];
        foreach ($reports as $report) {
            if ($report->place !== null) {
                $data['not_detected_locations'][] = $report->place;
            }
            if (!empty($report->users)) {
                foreach ($report->users as $user) {
                    $data['error_users_not_found'][] = $user->name;
                }
            }
            if ([] === $report->userRoles) {
                $data['no_users'][] = $report->geoPoint?->getName() . ': ' . $report->dateAction->format('d.m.Y');
            }
            if ([] === $report->blocks[0]->additional) {
                $data['report_block_no_additional'][] = $report->geoPoint?->getName() . ': ' . $report->dateAction->format('d.m.Y');
            }
            if (ReportBlockType::TYPE_UNDEFINED === $report->blocks[0]->type) {
                $data['warn_report_block_no_type'][] =
                    $report->geoPoint?->getName() . ': ' . $report->dateAction->format('d.m.Y')
                    . ', code: ' . $report->blocks[0]->code;
            }
        }

        $filename = '../var/data/kobo/contents.csv';
        $content = file_get_contents($filename);
        $contents = $this->parser->parseContents($content);
        $data['contents_count'] = count($contents);

        $data['content_error_index'] = [];
        foreach ($contents as $content) {
            if (!isset($reports[$content->reportIndex])) {
                $data['content_error_index'][] = $content->reportIndex . ': ' . $content->notes;
            }
        }

        $filename = '../var/data/kobo/informants.csv';
        $content = file_get_contents($filename);
        $informants = $this->parser->parseInformants($content);
        $data['informants_count'] = count($informants);

        $data['informants_error_index'] = [];
        $data['informants_error_locations'] = [];
        foreach ($informants as $informant) {
            if (1 !== count($informant->codeReports) || !isset($reports[$informant->codeReports[0]])) {
                $data['informants_error_index'][] = $informant->name;
            }
            if (1 !== count($informant->locations)) {
                $data['informants_error_locations'][] = '0 amount: ' . $informant->name;
            } else {
                $location = $this->locationService->detectLocationByFullPlace($informant->locations[0]);
                if (null === $location) {
                    $data['informants_error_locations'][] = $informant->locations[0];
                }
            }
        }

        $filename = '../var/data/kobo/organizations.csv';
        $content = file_get_contents($filename);
        $organizations = $this->parser->parseOrganizations($content);
        $data['organizations_count'] = count($organizations);

        $data['organizations_error_index'] = [];
        foreach ($organizations as $organization) {
            if (1 !== count($organization->codeReports) || !isset($reports[$organization->codeReports[0]])) {
                $data['organizations_error_index'][] = $organization->name;
            }
        }

        $filename = '../var/data/kobo/tags.csv';
        $content = file_get_contents($filename);
        $tags = $this->parser->parseTags($content);
        $data['tags_count'] = count($tags);

        $data['tag_error_index'] = [];
        foreach ($tags as $index => $tagList) {
            if (!isset($contents[$index])) {
                $data['tag_error_index'][] = $index . ': ' . implode(', ', $tagList);
            }
        }

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/kobo/save', name: 'app_import_kobo_save')]
    public function save(): Response
    {
        $expedition = $this->getExpedition();

        $data = [];

        $filename = '../var/data/kobo/reports.csv';
        $content = file_get_contents($filename);
        $reports = $this->parser->parseReports($content);
        $data['reports_count'] = count($reports);

        $filename = '../var/data/kobo/contents.csv';
        $content = file_get_contents($filename);
        $contents = $this->parser->parseContents($content);
        $data['contents_count'] = count($contents);

        $filename = '../var/data/kobo/informants.csv';
        $content = file_get_contents($filename);
        $informants = $this->parser->parseInformants($content);
        $data['informants_count'] = count($informants);

        $filename = '../var/data/kobo/organizations.csv';
        $content = file_get_contents($filename);
        $organizations = $this->parser->parseOrganizations($content);
        $data['organizations_count'] = count($organizations);

        $filename = '../var/data/kobo/tags.csv';
        $content = file_get_contents($filename);
        $tags = $this->parser->parseTags($content);
        $data['tags_count'] = count($tags);

        $this->reportManager->saveKoboReports(
            $expedition,
            $reports,
            $contents,
            $organizations,
            $informants,
            $tags
        );

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }
}
