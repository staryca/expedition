<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\BsuDto;
use App\Dto\InformantDto;
use App\Dto\OrganizationDto;
use App\Dto\StudentDto;
use App\Entity\Expedition;
use App\Entity\Report;
use App\Entity\Tag;
use App\Manager\ReportManager;
use App\Parser\BsuParser;
use App\Repository\ExpeditionRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportBsuController extends AbstractController
{
    private const EXPEDITION_ID = 8;

    public function __construct(
        private readonly BsuParser $parser,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly TagRepository $tagRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ReportManager $reportManager,
    ) {
    }

    #[Route('/import/bsu', name: 'app_import_bsu')]
    public function index(): Response
    {
        set_time_limit(2600);
        $baseUrl = $this->getParameter('bsu_url');

        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );

        $reports = $this->getAllImportedReports($expedition);
        $future = [];
        $offsetIds = [];
        $tags = [];

        $files = [
            "121891" => " Брэсцкая вобласць",
            "122473" => " Мінская вобласць",
            "122673" => " Віцебская вобласць",
            "122675" => " Гродзенская вобласць",
            "122677" => " Магілёўская вобласць",
            "123575" => " Гомельская вобласць",
            "124181" => " Запісы без указання вобласці"
        ];
        foreach ($files as $id => $temp) {
            $filename = '../var/data/bsu/' . $id . '.html';
            $content = file_get_contents($filename);
            /*
            $content = file_get_contents(
                $baseUrl . $id,
                false,
                stream_context_create($arrContextOptions)
            );
            */
            $dto = $this->parser->parseContent($content);

            if (!$dto->id) {
                $dto->id = (int) $id;
            }

            if (!isset($reports[$id])) {
                $report = $this->parser->createReport($dto, $expedition);

                $this->entityManager->persist($report);
                $this->entityManager->flush();

                $reports[$dto->id] = $report;
            }

            if (isset($dto->dc['DC.subject'])) {
                $this->addTags($tags, $this->parser->getTags($dto->dc['DC.subject']));
            }

            // Future reports
            foreach ($dto->links as $_id => $link) {
                if (!isset($reports[$_id]) && !isset($future[$_id])) {
                    $future[$_id] = $link;
                }
            }
            foreach ($dto->children as $_id => $link) {
                if (!isset($reports[$_id]) && !isset($future[$_id])) {
                    $future[$_id] = $link;
                }
            }

            if ($dto->total > 20) {
                $future[$id] = $dto;
            }
        }

        foreach ($future as $id => $mixed) {
            if ($id < 100000) {
                continue;
            }

            $dto = null;
            if (!isset($reports[$id])) {
                $content = file_get_contents(
                    $baseUrl . $id,
                    false,
                    stream_context_create($arrContextOptions)
                );
                $dto = $this->parser->parseContent($content);

                if (!$dto->id) {
                    $dto->id = $id;
                }

                $report = $this->parser->createReport($dto, $expedition);

                $this->entityManager->persist($report);
                $this->entityManager->flush();

                $reports[$dto->id] = $report;

                foreach ($dto->children as $_id => $link) {
                    if (!isset($reports[$_id]) && !isset($offsetIds[$_id])) {
                        $offsetIds[$_id] = $link;
                    }
                }

                if (isset($dto->dc['DC.subject'])) {
                    $this->addTags($tags, $this->parser->getTags($dto->dc['DC.subject']));
                }
            } elseif ($mixed instanceof BsuDto) {
                $dto = $mixed;
            }

            if ($dto instanceof BsuDto && $dto->total > 20) {
                $url = $baseUrl . $id . '?offset=';
                $offset = 20;
                while ($offset <= $dto->total) {
                    $content = file_get_contents(
                        $url . $offset,
                        false,
                        stream_context_create($arrContextOptions)
                    );
                    $_dto = $this->parser->parseContent($content);

                    foreach ($_dto->children as $_id => $link) {
                        if (!isset($reports[$_id]) && !isset($offsetIds[$_id])) {
                            $offsetIds[$_id] = $link;
                        }
                    }
                    $dto->children += $_dto->children;

                    $offset += 20;
                }

                $report = $reports[$dto->id];
                $_temp = $report->getTemp();
                $_temp['children'] = $dto->children;
                $report->setTemp($_temp);

                $this->entityManager->flush();
            }
        }

        foreach ($offsetIds as $id => $title) {
            if (!isset($reports[$id])) {
                $content = file_get_contents(
                    $baseUrl . $id,
                    false,
                    stream_context_create($arrContextOptions)
                );
                $dto = $this->parser->parseContent($content);

                $report = $this->parser->createReport($dto, $expedition);

                $this->entityManager->persist($report);
                $this->entityManager->flush();

                $reports[$dto->id] = $report;

                if (isset($dto->dc['DC.subject'])) {
                    $this->addTags($tags, $this->parser->getTags($dto->dc['DC.subject']));
                }
            }
        }

        $this->saveTags($tags);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $offsetIds,
        ]);
    }

    private function getAllImportedReports(Expedition $expedition): array
    {
        $reports = [];
        foreach ($expedition->getReports() as $report) {
            $code = (int) $report->getCode();
            if ($code > 100000) {
                $reports[$code] = $report;
            }
        }

        return $reports;
    }

    /**
     * @param Expedition $expedition
     * @return array<string, Report>
     */
    private function getAllVisibleReports(Expedition $expedition): array
    {
        $reports = [];
        foreach ($expedition->getReports() as $report) {
            $code = (int) $report->getCode();
            if ($code === 0) {
                $reports[$this->getReportIndex($report)] = $report;
            }
        }

        return $reports;
    }

    /**
     * @param Expedition $expedition
     * @return array<string, Report>
     */
    private function getAllDistrictsReport(Expedition $expedition): array
    {
        $reports = [];
        foreach ($expedition->getReports() as $report) {
            $code = (int) $report->getCode();
            $data = $report->getTemp();
            if ($code > 100000 && $data['total'] > 0) {
                $reports[$code] = $report;
            }
        }

        return $reports;
    }

    /**
     * @param Expedition $expedition
     * @return array<string, Report>
     */
    private function getAllNeedGeo(Expedition $expedition): array
    {
        $reports = [];
        foreach ($expedition->getReports() as $report) {
            $code = (int) $report->getCode();
            $data = $report->getTemp();
            if (
                $code > 100000
                && !empty(isset($data['dc']['DCTERMS.spatial']) && $data['locationText'])
                && $report->getGeoPoint() === null
            ) {
                $reports[$code] = $report;
            }
        }

        return $reports;
    }

    /**
     * @param Expedition $expedition
     * @return array<string, Report>
     */
    private function getAllItems(Expedition $expedition): array
    {
        $reports = [];
        foreach ($expedition->getReports() as $report) {
            $code = (int) $report->getCode();
            $data = $report->getTemp();
            if (
                $code > 100000
                && !empty(isset($data['dc']['DCTERMS.spatial']) && $data['locationText'])
            ) {
                $reports[$code] = $report;
            }
        }

        return $reports;
    }

    private function getReportIndex(Report $report): string
    {
        if ($report->getGeoPoint() !== null) {
            return (string) $report->getGeoPoint()->getId();
        }

        return (string) $report->getGeoNotes();
    }

    private function addTags(&$allTags, array $newTags): void
    {
        foreach ($newTags as $tag) {
            if (!in_array($tag, $allTags, true)) {
                $allTags[] = $tag;
            }
        }
    }

    private function getTags(string $subject): array
    {
        $results = [];

        $_tags = explode('::', $subject);
        foreach ($_tags as $tag) {
            $tag = trim($tag);
            if (!in_array($tag, ['', 'ЭБ БГУ', 'Беларускі фальклор'])) {
                $results[] = $tag;
            }
        }

        return $results;
    }

    private function saveTags(array $tags): void
    {
        foreach ($tags as $tag) {
            $obj = $this->tagRepository->findOneBy(['name' => $tag]);

            if (!$obj) {
                $newTag = new Tag();
                $newTag->setName($tag);
                $newTag->setSortOrder(156);
                $newTag->setBase(false);

                $this->entityManager->persist($newTag);
                $this->entityManager->flush();
            }
        }
    }

    #[Route('/import/bsu/reports', name: 'app_import_bsu_reports')]
    public function reports(): Response
    {
        set_time_limit(600);

        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        $visibleReports = $this->getAllVisibleReports($expedition);
        $importedReports = $this->getAllImportedReports($expedition);

        foreach ($importedReports as $report) {
            $index = $this->getReportIndex($report);
            if (!isset($visibleReports[$index])) {
                $newReport = new Report($expedition);
                $newReport->setGeoPoint($report->getGeoPoint());
                $newReport->setGeoNotes($report->getGeoNotes());
                if ($report->getDateAction()) {
                    $newReport->setDateAction($report->getDateAction());
                }
                $newReport->setDateCreated($report->getDateCreated());

                $this->entityManager->persist($newReport);
                $this->entityManager->flush();

                $visibleReports[$index] = $newReport;
            }
        }

        return $this->render('import/show.json.result.html.twig', [
            'data' => $visibleReports,
        ]);
    }

    #[Route('/import/bsu/districts', name: 'app_import_bsu_districts')]
    public function districts(): Response
    {
        set_time_limit(600);

        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        $districts = $this->getAllDistrictsReport($expedition);
        $importedReports = $this->getAllImportedReports($expedition);

        $result = ['need' => []];
        $totalTotal = 0;
        $totalChildren = 0;
        $totalImported = 0;
        $totalGeo = 0;
        foreach ($districts as $report) {
            $data = $report->getTemp();
            $total = $data['total'];
            $children = $data['children'];
            $imported = 0;
            $hasGeo = 0;
            foreach ($children as $id => $child) {
                if (isset($importedReports[$id])) {
                    $imported++;
                    if ($importedReports[$id]->getGeoPoint() !== null) {
                        $hasGeo++;
                    }
                }
            }
            $result[] = [
                'id' => $report->getCode(),
                'name' => $report->getGeoNotes(),
                'total' => $total,
                'children' => count($children),
                'imported' => $imported,
                'hasGeo' => $hasGeo,
            ];
            if ($total !== $hasGeo) {
                $result['need'][] = $report->getGeoNotes() . ' (' . $hasGeo . '/' . $total . ')';
            }

            $totalTotal += $total;
            $totalChildren += count($children);
            $totalImported += $imported;
            $totalGeo += $hasGeo;
        }
        $result['total'] = [
            'total' => $totalTotal,
            'children' => $totalChildren,
            'imported' => $totalImported,
            'totalGeo' => $totalGeo,
        ];

        return $this->render('import/show.json.result.html.twig', [
            'data' => $result,
        ]);
    }

    #[Route('/import/bsu/geo', name: 'app_import_bsu_geo')]
    public function geo(): Response
    {
        set_time_limit(600);

        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        $geoReports = $this->getAllNeedGeo($expedition);
        $setGeo = [];

        foreach ($geoReports as $report) {
            if ($this->parser->updateGeo($report)) {
                $setGeo[$report->getGeoNotes()] = (string) $report->getGeoPoint();
            }
        }

        $this->entityManager->flush();

        return $this->render('import/show.json.result.html.twig', [
            'data' => $setGeo,
        ]);
    }

    #[Route('/import/bsu/informant', name: 'app_import_bsu_informant')]
    public function informant(): Response
    {
        set_time_limit(600);

        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }
        $stat['memory A'] = memory_get_usage(true) / 1024 / 1024;

        $stat = [];

        $allReports = $this->getAllItems($expedition);
        $stat['All reports'] = count($allReports);
        $stat['memory 1'] = memory_get_usage(true) / 1024 / 1024;

        $personsBsu = [];
        $reportsData = [];
        foreach ($allReports as $code => $report) {
            $bsuDto = (new BsuDto())->make($report->getTemp());
            $reportsData[$code] = $this->parser->createReportData($bsuDto);
            $persons = $this->parser->getBsuPersonsFromAuthors($bsuDto->authors, $reportsData[$code]);
            foreach ($persons as $person) {
                $personsBsu[] = $person;
            }
        }
        usort($personsBsu, static function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });
        $stat['All reports Data'] = count($reportsData);
        $stat['memory 2'] = memory_get_usage(true) / 1024 / 1024;
        unset($expedition);
        $stat['memory 3'] = memory_get_usage(true) / 1024 / 1024;

        /** @var array<OrganizationDto> $organizations */
        $organizations = [];
        /** @var array<InformantDto> $informants */
        $informants = [];
        /** @var array<StudentDto> $students */
        $students = [];

        $this->parser->getOrganizations($personsBsu, $organizations);
        $stat['Organizations'] = count($organizations);

        $this->parser->getInformantsFromOrganizations($organizations, $informants);
        $stat['Informants in organizations'] = count($informants);

        $this->parser->getStudents($personsBsu, $students);
        usort($students, static function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });
        $stat['Students'] = count($students);
        $stat['memory 4'] = memory_get_usage(true) / 1024 / 1024;

        $this->parser->getInformants($personsBsu, $informants);
        $stat['All informants'] = count($informants);
        $stat['memory 5'] = memory_get_usage(true) / 1024 / 1024;

        // Compare students and informants
        $sameStudents = $this->parser->compareInformantsAndStudents($informants, $students);
        $stat['Compare informants & students'] =
            ['moved' => count($sameStudents), 'inf' => count($informants), 'stud' => count($students)];
        $stat['memory 6'] = memory_get_usage(true) / 1024 / 1024;

        // Merge the same informants
        $mergedInformants = $this->parser->mergeSameInformants($informants);
        $stat['Merged informants'] = ['merged' => count($mergedInformants), 'inf' => count($informants)];
        $stat['memory 7'] = memory_get_usage(true) / 1024 / 1024;

        // Informant in (3+ locations OR 2 location with only surname) => student
        $sameInformants = $this->parser->detectStudentsByLocations($informants, $students);
        $stat['Compare informants by locations'] =
            ['moved' => count($sameInformants), 'inf' => count($informants), 'stud' => count($students)];
        $stat['memory 8'] = memory_get_usage(true) / 1024 / 1024;

        // Only similar names
        $maybeStudents = $this->parser->detectStudentsByNames($informants, $students);
        $stat['Only similar names'] =
            ['moved' => count($maybeStudents), 'inf' => count($informants), 'stud' => count($students)];
        $stat['memory 9'] = memory_get_usage(true) / 1024 / 1024;

        // Detect informants in organizations (other => students)
        $orgInformants = $this->parser->detectInformantsInOrganizations($organizations, $informants, $students);
        $stat['Detect informants in organizations'] =
            ['orgInfs' => count($orgInformants), 'inf' => count($informants), 'stud' => count($students)];
        $stat['memory 10'] = memory_get_usage(true) / 1024 / 1024;
        unset($students);
        $stat['memory 11'] = memory_get_usage(true) / 1024 / 1024;

        $infByBirth = ['with_birth' => 0, 'without_birth' => 0];
        foreach ($informants as $informant) {
            if (null === $informant->birth) {
                ++$infByBirth['without_birth'];
            } else {
                ++$infByBirth['with_birth'];
            }
        }
        $stat['Informants by birth'] = $infByBirth;

        $types = [];
        foreach ($reportsData as $report) {
            foreach ($report->blocks as $block) {
                foreach ($block->files as $file) {
                    if (!isset($types[$file['type']])) {
                        $types[$file['type']] = 1;
                    } else {
                        $types[$file['type']]++;
                    }
                }
            }
        }
        $stat['Types of files'] = $types;

        return $this->render('import/show.json.result.html.twig', [
            'data' => $stat,
        ]);
    }

    #[Route('/import/bsu/save', name: 'app_import_bsu_save')]
    public function save(): Response
    {
        set_time_limit(600);
        ini_set("memory_limit", "28G");

        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }
        $sr['m1'] = memory_get_usage(true) / 1024 / 1024;

        $allReports = $this->getAllItems($expedition);

        $reportsData = [];
        $personsBsu = [];
        foreach ($allReports as $code => $report) {
            $bsuDto = (new BsuDto())->make($report->getTemp());
            $reportsData[$code] = $this->parser->createReportData($bsuDto);
            $persons = $this->parser->getBsuPersonsFromAuthors($bsuDto->authors, $reportsData[$code]);
            foreach ($persons as $person) {
                $personsBsu[] = $person;
            }
        }
        usort($personsBsu, static function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });

        $sr['m2'] = memory_get_usage(true) / 1024 / 1024;

        /** @var array<OrganizationDto> $organizations */
        $organizations = [];
        /** @var array<InformantDto> $informants */
        $informants = [];
        /** @var array<StudentDto> $students */
        $students = [];

        $this->parser->getOrganizations($personsBsu, $organizations);
        $this->parser->getInformantsFromOrganizations($organizations, $informants);
        $this->parser->getStudents($personsBsu, $students);
        usort($students, static function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });
        $this->parser->getInformants($personsBsu, $informants);

        // Compare students and informants
        $this->parser->compareInformantsAndStudents($informants, $students);

        // Merge the same informants
        $this->parser->mergeSameInformants($informants);

        // Informant in (3+ locations OR 2 location with only surname) => student
        $this->parser->detectStudentsByLocations($informants, $students);

        // Only similar names
        $this->parser->detectStudentsByNames($informants, $students);

        // Detect informants in organizations (other => students)
        $this->parser->detectInformantsInOrganizations($organizations, $informants, $students);
        unset($students);

        // Reports to DB
        $reportBlocksData = $this->parser->getReportBlocks($organizations, $informants);
        $this->parser->mergeReportBlocks($reportsData, $reportBlocksData);

        $reportsDataGroupByLocation = [];
        foreach ($reportsData as $reportData) {
            $key = ($reportData->geoPoint ? $reportData->geoPoint->getId() : $reportData->place)
                . '-' . $reportData->dateAction?->format('Y');
            if (!isset($reportsDataGroupByLocation[$key])) {
                $reportsDataGroupByLocation[$key][] = $reportData->code;
            } else {
                $reportsDataGroupByLocation[$key][] = $reportData->code;
            }
        }
        $sr['m7'] = memory_get_usage(true) / 1024 / 1024;

        // Save visible reports
        $this->reportManager->saveBsuReports(
            $expedition,
            $reportsData,
            $reportsDataGroupByLocation,
            $organizations,
            $informants,
        );

        return $this->render('import/show.json.result.html.twig', [
            'data' => $sr,
        ]);
    }
}
