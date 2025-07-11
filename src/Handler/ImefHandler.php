<?php

declare(strict_types=1);

namespace App\Handler;

use App\Dto\ImefDto;
use App\Entity\Expedition;
use App\Entity\Type\CategoryType;
use App\Parser\ImefParser;

class ImefHandler
{
    public function __construct(
        private readonly ImefParser $parser,
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
        $folder = $newFolders[44];
        //foreach ($newFolders as $i => $folder) {
        //    if (count($dtos) > 100) {
        //        break;
        //   }

        $content = file_get_contents(
            $baseUrl . $folder,
            false,
            stream_context_create($arrContextOptions)
        );
        $dtos = $this->parser->parseItem($content);
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
            }

            foreach (ImefParser::BAD_TAGS as $tag) {
                if (str_contains($tags, $tag)) {
                    $badCategory[] = $dto->name;
                }
            }

            foreach (ImefParser::BAD_WORDS as $word) {
                if (str_contains($dto->name, $word)) {
                    $badNames[$dto->name] = $dto->category;
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
            'badNames' => $badNames,
            'informantNotesAsLocation' => $informantNotes,
            'badLocations' => $badLocations,
            'tagToCategory' => $tagCategory,
            'tags' => $this->getTagTree($dtos),
        ];

        return $data;
    }

    /**
     * @param array<ImefDto> $dtos
     */
    public function saveDtos(array $dtos): void
    {
        foreach ($dtos as $dto) {
            foreach ($dto->informants as $informant) {
                if (count($informant->locations) > 0) {
                    $notes = implode(', ', $informant->locations);
                    $informant->notes = trim($informant->notes . ' ' . $notes);
                }
            }
        }
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
