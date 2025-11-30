<?php

declare(strict_types=1);

namespace App\Handler;

use App\Dto\EpisodeDto;
use App\Dto\ImefDto;
use App\Dto\InformantDto;
use App\Dto\ReportBlockDataDto;
use App\Dto\ReportDataDto;
use App\Dto\UserDto;
use App\Entity\Expedition;
use App\Entity\Report;
use App\Entity\Type\CategoryType;
use App\Entity\Type\ReportBlockType;
use App\Entity\Type\UserRoleType;
use App\Manager\ReportManager;
use App\Parser\ImefParser;
use App\Repository\ExpeditionRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Exception;

class ImefHandler
{
    private const EXPEDITION_ID = 10; // 10
    private ?Expedition $expedition = null;

    public function __construct(
        private readonly ImefParser $parser,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ReportManager $reportManager,
        private readonly string $imefUrl,
    ) {
    }

    public function getExpedition(): Expedition
    {
        if ($this->expedition) {
            return $this->expedition;
        }

        $this->expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$this->expedition) {
            throw new Exception('Expedition not found');
        }

        return $this->expedition;
    }

    /**
     * @return array<ImefDto>
     */
    public function check(): array
    {
        $newFolders = $this->getNewFolders();
        $folderKey = array_rand($newFolders);

        $previousDateDayMonth = true;

        return $this->parsingOneFolder($previousDateDayMonth, $newFolders[$folderKey]);
    }

    /**
     * @return array<string>
     */
    public function getNewFolders(): array
    {
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );

        $content = file_get_contents(
            $this->imefUrl,
            false,
            stream_context_create($arrContextOptions)
        );
        $folders = $this->parser->parseCatalog($content);

        $importedFolders = $this->getAllImportedFolders();

        return array_diff($folders, $importedFolders);
    }

    public function parsingOneFolder(bool &$previousDateDayMonth, string $folder): array
    {
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );

        $content = file_get_contents(
            $this->imefUrl . $folder,
            false,
            stream_context_create($arrContextOptions)
        );

        return $this->parser->parseItem($previousDateDayMonth, $content, $folder);
    }

    /**
     * @param array<ImefDto> $dtos
     * @return array
     */
    public function getViewData(array $dtos): array
    {
        $tagCategory = [];
        $badCategory = [];
        $unknownCategory = [];
        $badNames = [];
        $informantLocations = [];
        $informantNotes = [];
        $emptyYears = [];
        $badLocations = [];
        foreach ($dtos as $dto) {
            $tags = implode('#', $dto->tags);
            if ($dto->category !== null) {
                $dto->content = CategoryType::TYPES[$dto->category] . ' === ' . $dto->content;

                if (!isset($tagCategory[$tags])) {
                    $tagCategory[$tags] = [];
                }
                if (!in_array($dto->category, $tagCategory[$tags], true)) {
                    $tagCategory[$tags][] = $dto->category;
                }

                if ($dto->category === CategoryType::OTHER && !empty($dto->tags)) {
                    $unknownCategory[] = $tags;
                }
            }

            foreach (ImefParser::BAD_TAGS as $tag) {
                if (str_contains($tags, $tag)) {
                    $badCategory[] = $dto->name;
                }
            }

            foreach (ImefParser::BAD_WORDS as $word) {
                if (str_contains($dto->name, $word)) {
                    $badNames[$dto->name] = CategoryType::getSingleName($dto->category);
                }
            }

            foreach ($dto->informants as $informant) {
                if (count($informant->locations) > 0) {
                    $locations = implode(', ', $informant->locations);
                    $informantLocations[$informant->name] = $locations;
                }
                if (!empty($informant->notes)) {
                    $informantNotes[$informant->name] = $informant->notes;
                }
                if ($informant->birthDay !== null && $informant->birthDay->year < 1800) {
                    $emptyYears[$informant->name] = $informant->birthDay->format('Y-m-d');
                }
            }

            if (!empty($dto->place)) {
                $badLocations[$dto->place] = '-';
            }
        }
        ksort($badLocations);

        $data = [
            'items' => $dtos,
            'badCategory' => $badCategory,
            'unknownCategory' => $unknownCategory,
            'badNames' => $badNames,
            'informantLocations' => $informantLocations,
            'informantNotes' => $informantNotes,
            'emptyYears' => $emptyYears,
            'badLocations' => $badLocations,
            'tagToCategory' => $tagCategory,
            'tags' => $this->getTagTree($dtos),
            'reports' => $this->createReportsData($dtos),
        ];

        return $data;
    }

    /**
     * @param array<ImefDto> $dtos
     * @return array<ReportDataDto>
     * @throws \Exception
     */
    public function createReportsData(array &$dtos): array
    {
        $hashReports = [];
        $hashBlocks = [];
        /** @var array<ReportDataDto> $reports */
        $reports = [];
        $reportKey = -1;
        $blockKey = -1;
        $episodeKey = -1;

        foreach ($dtos as $dto) {
            /* for Report */
            $users = array_map(static function (UserDto $user) {
                return $user->name;
            }, $dto->users);
            $hashReport = ($dto->date?->format('Ymd')) . '_' . $dto->getPlaceHash() . '_' . implode('-', $users);
            $_key = array_search($hashReport, $hashReports, true);
            if (false !== $_key && !$dto->isEmptyPlace()) {
                $reportKey = $_key;
                $blockKey = count($reports[$reportKey]->blocks) - 1;
            } elseif (
                $reportKey === -1
                || false === $_key
                || $dto->isEmptyPlace()
            ) {
                $reportKey = count($hashReports);
                $hashReports[$reportKey] = $hashReport;
                $blockKey = -1;

                $reports[$reportKey] = new ReportDataDto();
                $reports[$reportKey]->geoPoint = $dto->geoPoint;
                $reports[$reportKey]->place = $dto->place;
                $reports[$reportKey]->dateCreated = CarbonImmutable::now();
                $reports[$reportKey]->dateAction = $dto->date;
                $reports[$reportKey]->temp['folder'] = $dto->folder;

                foreach ($dto->users as $user) {
                    $user->roles = [UserRoleType::ROLE_INTERVIEWER];
                    $reports[$reportKey]->users[] = $user;
                }
            }
            if (!isset($reports[$reportKey])) {
                throw new \Exception(
                    sprintf('Report key %s not found (1). Imef: %s', $reportKey, $dto->content)
                );
            }

            /* for ReportBlock */
            if ($blockKey > 0 && !isset($reports[$reportKey]->blocks[$blockKey])) {
                throw new \Exception(
                    sprintf('Block key %s not found (1). Imef: %s', $blockKey, $dto->name)
                );
            }

            $hashBlock = implode('=', array_map(static function (InformantDto $informantDto) {
                return $informantDto->getHash();
            }, $dto->informants));

            if (isset($hashBlocks[$reportKey][$hashBlock])) {
                $blockKey = $hashBlocks[$reportKey][$hashBlock];
                if (!isset($reports[$reportKey]->blocks[$blockKey])) {
                    throw new \Exception(
                        sprintf('Block key %s not found (2). Imef: %s, %s', $blockKey, $dto->name, var_export($hashBlocks[$reportKey], true))
                    );
                }
            } else {
                $blockKey++;
                $hashBlocks[$reportKey][$hashBlock] = $blockKey;

                $reports[$reportKey]->blocks[$blockKey] = new ReportBlockDataDto();
                $reports[$reportKey]->blocks[$blockKey]->type = ReportBlockType::TYPE_CONVERSATION;
                $reports[$reportKey]->blocks[$blockKey]->informants = $dto->informants;
            }

            $episode = new EpisodeDto($dto->category, $dto->name);
            $episode->tags = $dto->tags;
            $reports[$reportKey]->blocks[$blockKey]->addEpisode((string) ++$episodeKey, $episode);
        }

        return $reports;
    }

    /**
     * @param array<ImefDto> $dtos
     * @return array<Report>
     * @throws Exception
     * @throws \Exception
     */
    public function saveDtos(array $dtos): array
    {
        foreach ($dtos as $dto) {
            foreach ($dto->informants as $informant) {
                if (count($informant->locations) > 0) {
                    $notes = implode(', ', $informant->locations);
                    $informant->notes = trim($informant->notes . ' ' . $notes);
                }
            }
        }

        $reportsData = $this->createReportsData($dtos);

        return $this->reportManager->saveSubjects($this->getExpedition(), [], [], $reportsData, []);
    }

    private function getAllImportedFolders(): array
    {
        $expedition = $this->getExpedition();

        $folders = [];
        foreach ($expedition->getReports() as $report) {
            $folder = $report->getTempValue('folder');
            $folders[$folder] = 1;
        }

        return array_keys($folders);
    }

    /**
     * @param array<ImefDto> $dtos
     * @return array
     */
    private function getTagTree(array $dtos): array
    {
        $tree = [];

        foreach ($dtos as $dto) {
            $tags = $dto->tags;

            $tag0 = array_shift($tags);
            if ($tag0 !== null) {
                if (!isset($tree[$tag0])) {
                    $tree[$tag0] = [];
                }
                $tag1 = array_shift($tags);
                if ($tag1 !== null) {
                    if (!isset($tree[$tag0][$tag1])) {
                        $tree[$tag0][$tag1] = [];
                    }
                    $tag2 = array_shift($tags);
                    if ($tag2 !== null) {
                        if (!isset($tree[$tag0][$tag1][$tag2])) {
                            $tree[$tag0][$tag1][$tag2] = [];
                        }
                        $tag3 = array_shift($tags);
                        if ($tag3 !== null) {
                            if (!isset($tree[$tag0][$tag1][$tag2][$tag3])) {
                                $tree[$tag0][$tag1][$tag2][$tag3] = [];
                            }
                            $tag4 = array_shift($tags);
                            if ($tag4 !== null) {
                                if (!isset($tree[$tag0][$tag1][$tag2][$tag3][$tag4])) {
                                    $tree[$tag0][$tag1][$tag2][$tag3][$tag4] = [];
                                }
                                if (!empty($tags)) {
                                    $tree[$tag0][$tag1][$tag2][$tag3][$tag4][] = implode('#', $tags);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $tree;
    }
}
