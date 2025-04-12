<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\FileDto;
use App\Dto\FileMarkerDto;
use App\Dto\ReportBlockDataDto;
use App\Dto\ReportDataDto;
use App\Entity\Type\CategoryType;
use App\Entity\Type\FileType;
use App\Entity\Type\ReportBlockType;
use App\Service\LocationService;
use App\Service\PersonService;
use Carbon\Carbon;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;

class VopisParser
{
    private const NAME_TECHNICAL = '***';
    private const NAME_GROUP = '+++';

    public function __construct(
        private readonly LocationService $locationService,
        private readonly PersonService $personService,
    ) {
    }

    private static function isTechName(string $name): bool
    {
        return in_array($name, [self::NAME_TECHNICAL, self::NAME_GROUP]);
    }

    /**
     * @param string $content
     * @param bool $withTime
     * @return FileDto[]
     * @throws Exception
     * @throws InvalidArgument
     */
    public function parse(string $content, bool $withTime): array
    {
        /** @var array<FileDto> $files */
        $files = [];
        $key = -1;

        $csv = Reader::createFromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        foreach ($csv->getRecords() as $record) {
            if ($record[0] === '') {
                continue;
            }
            if (
                $key < 0
                || (
                    $record[1] === ''
                    && $record[2] === ''
                    && ($withTime || !is_numeric(mb_substr($record[0], 0, 1)))
                )
            ) {
                $key++;
                $files[$key] = new FileDto($record[0]);
                $files[$key]->type = FileType::TYPE_AUDIO;
            } else {
                $marker = new FileMarkerDto();
                if ($key === 0 && $files[$key]->markers === []) {
                    $marker->isNewBlock = true;
                }

                if ($withTime) {
                    $time = trim($record[0]);
                    $marker->timeFrom = ($time[1] === ':' ? '0' : '') . $time;
                }
                $index = $withTime ? 1 : 0;

                $name = trim($record[$index]);
                $notes = trim($record[$index + 1]);
                if (($pos = mb_strpos($name, ' ')) !== false) {
                    $name = mb_substr($name, $pos + 1);
                }
                $category = CategoryType::findId($name, '', false);
                if ($category !== null) {
                    $marker->category = $category;
                } else {
                    if ($name === self::NAME_TECHNICAL) {
                        $marker->isNewBlock = true;
                    }
                    $marker->category = $name !== self::NAME_GROUP
                        ? (CategoryType::findId($notes, '') ?? CategoryType::OTHER)
                        : CategoryType::OTHER;
                    if ($marker->category === CategoryType::OTHER && self::isTechName($name)) {
                        $marker->notes = $notes;
                    }
                    if (!self::isTechName($name)) {
                        $marker->name = $name;
                        $marker->notes = $notes;
                        if ($marker->category === CategoryType::OTHER) {
                            $marker->category = CategoryType::STORY;
                        }
                    }
                }

                $place = trim($record[$index + 2]);
                $location = $this->locationService->detectLocationByFullPlace($place);
                if ($location) {
                    $marker->geoPoint = $location;
                } else {
                    $marker->place = $place;
                }

                if (isset($record[$index + 3])) {
                    $marker->informantsText = trim($record[$index + 3]);
                }
                $files[$key]->markers[] = $marker;
            }
        }

        foreach ($files as $file) {
            $keyPrev = null;
            foreach ($file->markers as $key => $marker) {
                if (null !== $keyPrev) {
                    $file->markers[$keyPrev]->timeTo = $marker->timeFrom;
                }
                $keyPrev = $key;
            }
        }

        return $files;
    }

    /**
     * @param array<FileDto> $files
     * @return array<ReportDataDto>
     */
    public function createReports(array &$files, Carbon $dateCreated): array
    {
        $reports = [];
        $reportKey = -1;
        $blockKey = -1;

        foreach ($files as $file) {
            foreach ($file->markers as $marker) {
                if ($marker->isNewBlock) {
                    if (
                        $reportKey === -1
                        || $reports[$reportKey]->geoPoint !== $marker->geoPoint
                        || $reports[$reportKey]->place !== $marker->place
                    ) {
                        $reportKey++;
                        $reports[$reportKey] = new ReportDataDto();
                        $reports[$reportKey]->geoPoint = $marker->geoPoint;
                        $reports[$reportKey]->place = $marker->place;
                        $reports[$reportKey]->dateCreated = $dateCreated;
                        $blockKey = 0;
                    } else {
                        $blockKey++;
                    }
                    $reports[$reportKey]->blocks[$blockKey] = new ReportBlockDataDto();
                    $reports[$reportKey]->blocks[$blockKey]->type = ReportBlockType::TYPE_CONVERSATION;

                    if (!empty($marker->informantsText)) {
                        $reports[$reportKey]->blocks[$blockKey]->informants =
                            $this->personService->getInformants($marker->informantsText);
                    }
                }
                $marker->reportKey = $reportKey;
                $marker->blockKey = $blockKey;
            }
        }

        return $reports;
    }
}
