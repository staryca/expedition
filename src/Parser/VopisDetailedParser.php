<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\FileDto;
use App\Dto\FileMarkerDto;
use App\Dto\SubjectDto;
use App\Entity\GeoPoint;
use App\Entity\Type\CategoryType;
use App\Entity\Type\FileType;
use App\Entity\Type\SubjectType;
use App\Helper\TextHelper;
use App\Parser\Columns\VopisDetailedColumns;
use App\Service\LocationService;
use Carbon\Carbon;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;

class VopisDetailedParser
{
    public function __construct(
        private readonly LocationService $locationService,
    ) {
    }

    /**
     * @param string $content
     * @return SubjectDto[]
     * @throws Exception
     * @throws InvalidArgument
     */
    public function parse(string $content): array
    {
        /** @var array<SubjectDto> $subjects */
        $subjects = [];
        $keySubject = -1;
        $keyFile = -2;
        $isPrevSubject = false;

        $csv = Reader::createFromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $key => $record) {
            if (mb_strlen($record[VopisDetailedColumns::NAME_TIME]) < 3) {
                continue;
            }
            if (
                $keySubject < 0
                || (
                    $record[VopisDetailedColumns::INFORMANTS] === ''
                    && $record[VopisDetailedColumns::CONTENT] === ''
                )
            ) {
                if (!$isPrevSubject) {
                    $name = trim($record[VopisDetailedColumns::NAME_TIME]);
                    if ($keySubject < 0 || $subjects[$keySubject]->name !== $name) {
                        $keySubject++;
                        $subjects[$keySubject] = new SubjectDto();
                        $subjects[$keySubject]->type = SubjectType::TYPE_AUDIO;
                        $subjects[$keySubject]->name = $name;
                        $keyFile = -1;
                    }
                    $isPrevSubject = true;
                } else {
                    $keyFile++;
                    $name = TextHelper::replaceLetters($record[VopisDetailedColumns::NAME_TIME]);
                    $subjects[$keySubject]->files[$keyFile] = new FileDto($name);
                    $subjects[$keySubject]->files[$keyFile]->type = FileType::TYPE_AUDIO;
                    $isPrevSubject = false;
                }
            } else {
                if (!isset($subjects[$keySubject], $subjects[$keySubject]->files[$keyFile])) {
                    throw new \Exception(sprintf(
                        'Subject not found (%d, %d) row #%d: %s',
                        $keySubject,
                        $keyFile,
                        $key,
                        $record[VopisDetailedColumns::CONTENT]
                    ));
                }

                $marker = new FileMarkerDto();

                $time = trim($record[VopisDetailedColumns::NAME_TIME]);
                $marker->timeFrom = ($time[1] === ':' ? '0' : '') . $time;

                $date = trim($record[VopisDetailedColumns::DATE]);
                if ($date !== '') {
                    $marker->dateAction = Carbon::createFromFormat('d.m.Y', $date);
                }

                $name = trim($record[VopisDetailedColumns::CONTENT]);
                $notes = trim($record[VopisDetailedColumns::ADDITIONAL]);
                $pos = mb_strpos($name, ') ');
                if ($pos !== false && $pos < 3) {
                    $name = trim(mb_substr($name, $pos + 1));
                }
                $category = CategoryType::findId($name, '', false);
                if ($category !== null) {
                    $marker->category = $category;
                } else {
                    $marker->category = CategoryType::findId($notes, '') ?? CategoryType::OTHER;
                    $marker->name = $name;
                    $marker->notes = $notes;
                    if ($marker->category === CategoryType::OTHER) {
                        $marker->category = CategoryType::STORY;
                    }
                }

                $locationText = '';
                $marker->geoPoint = $this->parsePlace(
                    TextHelper::replaceLetters($record[VopisDetailedColumns::CURRENT_VILLAGE]),
                    TextHelper::replaceLetters($record[VopisDetailedColumns::CURRENT_DISTRICT]),
                    $locationText
                );
                if (null === $marker->geoPoint) {
                    $marker->place = $locationText;
                }

                $marker->informantsText = TextHelper::replaceLetters($record[VopisDetailedColumns::INFORMANTS]);
                $subjects[$keySubject]->files[$keyFile]->markers[] = $marker;

                $recordText = TextHelper::replaceLetters($record[VopisDetailedColumns::RECORD]);
                if ($recordText !== '') {
                    $marker->others[FileMarkerDto::OTHER_RECORD] = $recordText;
                }

                $locationText = '';
                $geoPoint = $this->parsePlace(
                    TextHelper::replaceLetters($record[VopisDetailedColumns::BIRTH_VILLAGE]),
                    TextHelper::replaceLetters($record[VopisDetailedColumns::BIRTH_DISTRICT]),
                    $locationText
                );
                if (null !== $geoPoint) {
                    $marker->others[FileMarkerDto::OTHER_BIRTH_GEO_POINT] = $geoPoint;
                } elseif (!empty($locationText)) {
                    $marker->others[FileMarkerDto::OTHER_BIRTH_LOCATION] = $locationText;
                }

                $mention = TextHelper::replaceLetters($record[VopisDetailedColumns::MENTION]);
                if ($mention !== '') {
                    $marker->others[FileMarkerDto::OTHER_MENTION] = $mention;
                }
            }
        }

        foreach ($subjects as $subject) {
            foreach ($subject->files as $file) {
                $keyPrev = null;
                foreach ($file->markers as $key => $marker) {
                    if (null !== $keyPrev) {
                        $file->markers[$keyPrev]->timeTo = $marker->timeFrom;
                    }
                    $keyPrev = $key;
                }
            }
        }

        return $subjects;
    }

    private function parsePlace(string $place, string $district, string &$locationText): ?GeoPoint
    {
        $district = str_replace(['р.', 'р-н'], LocationService::DISTRICT, $district);
        if ($district !== '' && !str_contains($district, LocationService::DISTRICT)) {
            $district .= ' ' . LocationService::DISTRICT;
        }

        $place = TextHelper::cleanManySpaces($place);
        $district = TextHelper::cleanManySpaces($district);

        if (empty($place)) {
            $locationText = $district;
        } else {
            $locationText = $place . ($district === '' ? '' : ', ') . $district;
            return $this->locationService->detectLocationByFullPlace($locationText);
        }

        return null;
    }
}
