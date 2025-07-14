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
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Exception;

class ImefHandler
{
    public function __construct(
        private readonly ImefParser $parser,
        private readonly ReportManager $reportManager,
    ) {
    }

    /**
     * @param string $baseUrl
     * @param Expedition $expedition
     * @return array<ImefDto>
     */
    public function check(string $baseUrl, Expedition $expedition): array
    {
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );

        $content = file_get_contents(
            $baseUrl,
            false,
            stream_context_create($arrContextOptions)
        );
        $folders = $this->parser->parseCatalog($content);

        $importedFolders = $this->getAllImportedFolders($expedition);
        $newFolders = array_diff($folders, $importedFolders);

        $dtos = [];
        $folder = $newFolders[189]; //184
        //foreach ($newFolders as $i => $folder) {
        //    if (count($dtos) > 100) {
        //        break;
        //   }

        $content = file_get_contents(
            $baseUrl . $folder,
            false,
            stream_context_create($arrContextOptions)
        );
        $dtos = $this->parser->parseItem($content, $folder);
        //}

        return $dtos;
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
        $informantNotes = [];
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
                    $notes = implode(', ', $informant->locations);
                    $informantNotes[$informant->name] = $notes;
                    $informant->notes .= ' ' . $notes;
                }
            }

            if (!empty($dto->place)) {
                $badLocations[$dto->place] = '-';
            }
        }

        $data = [
            'items' => $dtos,
            'badCategory' => $badCategory,
            'unknownCategory' => $unknownCategory,
            'badNames' => $badNames,
            'informantNotesAsLocation' => $informantNotes,
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

        foreach ($dtos as $dto) {
            /* for Report */
            $users = array_map(static function (UserDto $user) {
                return $user->name;
            }, $dto->users);
            $hashReport = ($dto->date->format('Ymd')) . '_' . $dto->getPlaceHash() . '_' . implode('-', $users);
            $_key = array_search($hashReport, $reports, true);
            if (false !== $_key && !$dto->isEmptyPlace()) {
                $reportKey = $_key;
                if ($reportKey + 1 !== count($hashReports)) {
                    $blockKey = count($reports[$reportKey]->blocks) - 1;
                }
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
            $reports[$reportKey]->blocks[$blockKey]->episodes[] = $episode;
        }

        return $reports;
    }

    /**
     * @param Expedition $expedition
     * @param array<ImefDto> $dtos
     * @return array<Report>
     * @throws Exception
     * @throws \Exception
     */
    public function saveDtos(Expedition $expedition, array $dtos): array
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

        return $this->reportManager->saveSubjects($expedition, [], [], $reportsData, []);
    }

    private function getAllImportedFolders(Expedition $expedition): array
    {
        $folders = [];
        foreach ($expedition->getReports() as $report) {
            $folder = $report->getTempValue('folder');
            $folders[] = $folder;
        }

        return $folders;
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
