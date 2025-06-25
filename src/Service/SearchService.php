<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\File;
use App\Entity\FileMarker;
use App\Entity\ReportBlock;
use App\Entity\Type\FileType;
use App\Entity\Type\TaskStatus;
use App\Repository\FileMarkerRepository;

class SearchService
{
    public function __construct(
        private readonly FileMarkerRepository $fileMarkerRepository,
    ) {
    }

    public function generateSearchForBlock(ReportBlock $reportBlock): void
    {
        $searchIndex = $reportBlock->getUserNotes() ? $reportBlock->getUserNotes() . '. ' : '';

        $tags = '';
        foreach ($reportBlock->getTags() as $tag) {
            $tags .= $tag->getName() . ', ';
        }
        $tags = trim($tags, " ,");
        $searchIndex .= empty($tags) ? '' : $tags . '. ';

        $organization = $reportBlock->getOrganization();
        if ($organization) {
            $org = $organization->getName() . ', ' . $organization->getAddress() . ', '
                . $organization->getDescription() . '. ' . $organization->getNotes() . '. ';
            if ($organization->getOrganizationInformants()->count() > 0) {
                $text = 'Удзельнікі: ';
                foreach ($organization->getOrganizationInformants() as $organizationInformant) {
                    $informant = $organizationInformant->getInformant();
                    $text .= $informant->getSearchIndex() . '; ';
                }
                $text = trim($text, " ,");
                $org .= empty($text) ? '' : $text . '. ';
            }
            $org = trim($org, " ,.");
            $searchIndex .= $org . '. ';
        }

        if ($reportBlock->getInformants()->count() > 0) {
            $inf = 'Інфарманты: ';
            foreach ($reportBlock->getInformants() as $informant) {
                $inf .= $informant->getSearchIndex() . '; ';
            }
            $inf = trim($inf, " ,;");
            $searchIndex .= $inf . '. ';
        }

        if ($reportBlock->getTasks()->count() > 0) {
            $text = 'Заўвагі: ';
            $subtexts = [];
            foreach ($reportBlock->getTasks() as $task) {
                $subtext = '';
                if ($task->getStatus() === TaskStatus::TIP) {
                    $subtext .= 'наводка: ';
                }
                $subtext .= trim($task->getContent(), " .,;") . ', ';
                if ($task->getInformant() !== null) {
                    $informant = $task->getInformant();
                    $subtext .= $informant->getSearchIndex() . ', ';
                }
                $subtexts[] = trim($subtext, " ,");
            }
            $text .= implode('; ', $subtexts);
            $searchIndex .= $text . '. ';
        }

        if ($reportBlock->getDescription() !== null) {
            $searchIndex .= trim($reportBlock->getDescription(), " .,;\t\n\r\0\x0B") . '. ';
        }

        if ($reportBlock->getContentFile() !== null) {
            $searchIndex .= 'Змест: ' . $this->getSearchIndexForFile($reportBlock->getContentFile()) . '. ';
        }

        if ($reportBlock->getRealFiles()->count() > 0) {
            $text = 'Файлы: ';
            foreach ($reportBlock->getRealFiles() as $file) {
                $text .= $this->getSearchIndexForFile($file) . '. ';
            }
            $text = trim($text, " ,;");
            $searchIndex .= $text . '. ';
        }

        if (!empty($reportBlock->getPhotoNotes())) {
            $searchIndex .= 'Заўвагі к фота: ' . trim($reportBlock->getPhotoNotes(), " .,;\t\n\r\0\x0B") . '. ';
        }

        if (!empty($reportBlock->getVideoNotes())) {
            $searchIndex .= 'Заўвагі к відэа: ' . trim($reportBlock->getVideoNotes(), " .,;\t\n\r\0\x0B") . '. ';
        }

        $fileMarkers = $reportBlock->getFileMarkerGroups();
        if (!empty($fileMarkers)) {
            $text = 'Файлы: ';
            $files = $reportBlock->getFilesOfMarkers();
            foreach ($files as $file) {
                $text .= $this->getSearchIndexForFile($file, true, $fileMarkers[$file->getId()]) . '. ';
            }
            $searchIndex .= $text . '. ';
        }

        $searchIndex = trim(str_replace([' .', '..', ' ,', ',,'], ['.', '.', ',', ','], $searchIndex));

        $reportBlock->setSearchIndex($searchIndex);
    }

    /**
     * @param File $file
     * @param bool $partMarkers
     * @param array<FileMarker> $markers
     * @return string
     */
    private function getSearchIndexForFile(File $file, bool $partMarkers = false, array $markers = []): string
    {
        $text = '';

        if ($file->getType() === FileType::TYPE_VIRTUAL_CONTENT_LIST) {
            $subtexts = [];
            foreach ($file->getFileMarkers() as $fileMarker) {
                $texts = [];
                if (!empty($fileMarker->getName())) {
                    $texts['name'] = $fileMarker->getName();
                }
                $texts += $this->fileMarkerRepository->getTagNamesByMarker($fileMarker);
                if (!empty($fileMarker->getNotes())) {
                    $texts[] = trim($fileMarker->getNotes(), " ,.;");
                }
                $subtexts[] = implode(', ', $texts);
            }
            $text .= implode('; ', $subtexts);
        } else {
            if ($file->getFilename()) {
                $text .= $file->getFilename() . ', ';
            }
            if ($file->getSizeText()) {
                $text .= $file->getSizeText() . ', ';
            }
            if ($file->getComment()) {
                $text .= trim($file->getComment(), " ,.;") . ', ';
            }
            if (!empty($text)) {
                $text = trim($text, " ,.;") . '. ';
            }

            $printMarkers = $partMarkers ? $markers : $file->getFileMarkers();
            $subtexts = [];
            foreach ($printMarkers as $fileMarker) {
                $texts = [];
                if ($fileMarker->getStartTime()) {
                    $texts['time'] = $fileMarker->getStartTime()->format('H:i:s.u');
                }
                if (!empty($fileMarker->getName())) {
                    $texts['name'] = $fileMarker->getName();
                }
                $texts += $this->fileMarkerRepository->getTagNamesByMarker($fileMarker);
                if (!empty($fileMarker->getNotes())) {
                    $texts[] = trim($fileMarker->getNotes(), " ,.;");
                }
                if (!empty($fileMarker->getDecoding())) {
                    $texts[] = trim($fileMarker->getDecoding(), " ,.;");
                }
                $subtexts[] = implode(', ', $texts);
            }
            $text .= implode('; ', $subtexts);
        }

        return $text;
    }
}
