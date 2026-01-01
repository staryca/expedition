<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\FileDto;
use App\Dto\FileMarkerDto;
use App\Dto\SubjectDto;
use App\Entity\Type\CategoryType;
use App\Entity\Type\FileType;
use App\Entity\Type\SubjectType;
use App\Helper\TextHelper;
use App\Parser\Columns\VopisDetailedColumns;
use App\Service\CategoryService;
use App\Service\LocationService;
use Carbon\Carbon;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;

readonly class VopisDetailedParser
{
    public function __construct(
        private LocationService $locationService,
        private CategoryService $categoryService,
    ) {
    }

    public static function getColumns(): string
    {
        $columns = [
            VopisDetailedColumns::NAME_TIME,
            VopisDetailedColumns::DATE,
            VopisDetailedColumns::RECORD,
            VopisDetailedColumns::CURRENT_VILLAGE,
            VopisDetailedColumns::CURRENT_DISTRICT,
            VopisDetailedColumns::INFORMANTS,
            VopisDetailedColumns::BIRTH_VILLAGE,
            VopisDetailedColumns::BIRTH_DISTRICT,
            VopisDetailedColumns::MENTION,
            VopisDetailedColumns::CONTENT,
            VopisDetailedColumns::ADDITIONAL,
        ];

        return implode('; ', $columns);
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
        $isPrevFile = false;

        $csv = Reader::fromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $key => $record) {
            $nameTime = trim($record[VopisDetailedColumns::NAME_TIME]);
            if ($nameTime !== '' && mb_strlen($nameTime) < 3) {
                continue;
            }
            $content = trim($record[VopisDetailedColumns::CONTENT]);
            if ($nameTime === '' && $content === '') {
                continue;
            }

            if (
                $keySubject < 0
                || (
                    $record[VopisDetailedColumns::INFORMANTS] === ''
                    && $content === ''
                )
            ) {
                if (!$isPrevSubject) {
                    if ($keySubject < 0 || $subjects[$keySubject]->name !== $nameTime) {
                        $keySubject++;
                        $subjects[$keySubject] = new SubjectDto();
                        $subjects[$keySubject]->type = SubjectType::TYPE_AUDIO;
                        $subjects[$keySubject]->name = $nameTime;
                        $keyFile = -1;
                    }
                    $isPrevSubject = true;
                    $isPrevFile = false;
                } elseif (!$isPrevFile) {
                    $keyFile++;
                    $name = TextHelper::replaceLetters($nameTime);
                    $subjects[$keySubject]->files[$keyFile] = new FileDto($name);
                    $subjects[$keySubject]->files[$keyFile]->type = FileType::TYPE_AUDIO;
                    $isPrevFile = true;
                } else {
                    $note = TextHelper::replaceLetters($nameTime);
                    $notes = $subjects[$keySubject]->files[$keyFile]->notes;
                    $notes .= (empty($notes) ? '' : "\n") . $note;
                    $subjects[$keySubject]->files[$keyFile]->notes = $notes;
                }
            } else {
                $isPrevSubject = false;
                if (!isset($subjects[$keySubject], $subjects[$keySubject]->files[$keyFile])) {
                    throw new \Exception(sprintf(
                        'Subject not found (%d, %d) row #%d: %s',
                        $keySubject,
                        $keyFile,
                        $key,
                        $content
                    ));
                }

                $marker = new FileMarkerDto();

                if ('' !== $nameTime) {
                    $marker->timeFrom = ($nameTime[1] === ':' ? '0' : '') . $nameTime;
                }

                $date = trim($record[VopisDetailedColumns::DATE]);
                if ($date !== '') {
                    if (strlen($date) === 4) {
                        $marker->dateAction = Carbon::createFromDate($date, 1, 1); // 01/01 as unknown
                    } else {
                        try {
                            $marker->dateAction = Carbon::createFromFormat('d.m.Y', $date);
                        } catch (\Exception $e) {
                            throw new \Exception(sprintf(
                                'Bad date "%s", row #%d: %s',
                                $date,
                                $key,
                                $content
                            ));
                        }
                    }
                }

                $notes = trim($record[VopisDetailedColumns::ADDITIONAL]);
                $pos = mb_strpos($content, ') ');
                if ($pos !== false && $pos < 3) {
                    $content = trim(mb_substr($content, $pos + 1));
                }

                $category = $this->categoryService->detectCategory($content, $notes) ?? CategoryType::OTHER;
                $marker->category = $category !== CategoryType::OTHER ? $category : CategoryType::STORY;
                if (!CategoryType::isSystemType($category)) {
                    $marker->name = $content;
                    $marker->notes = $notes;
                }

                $locationText = '';
                $marker->geoPoint = $this->locationService->parsePlace(
                    TextHelper::replaceLetters($record[VopisDetailedColumns::CURRENT_VILLAGE]),
                    TextHelper::replaceLetters($record[VopisDetailedColumns::CURRENT_DISTRICT]),
                    null,
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
                $geoPoint = $this->locationService->parsePlace(
                    TextHelper::replaceLetters($record[VopisDetailedColumns::BIRTH_VILLAGE]),
                    TextHelper::replaceLetters($record[VopisDetailedColumns::BIRTH_DISTRICT]),
                    null,
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
}
