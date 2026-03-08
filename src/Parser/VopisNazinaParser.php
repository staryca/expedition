<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\FileDto;
use App\Dto\FileMarkerDto;
use App\Dto\SubjectDto;
use App\Entity\Type\CategoryType;
use App\Entity\Type\FileType;
use App\Entity\Type\SubjectType;
use App\Helper\DateHelper;
use App\Helper\TextHelper;
use App\Parser\Columns\VopisNazinaColumns;
use App\Service\CategoryService;
use App\Service\DanceService;
use App\Service\LocationService;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;

readonly class VopisNazinaParser
{
    private const string USER_NAZINA = 'Назіна';

    public function __construct(
        private LocationService $locationService,
        private CategoryService $categoryService,
        private DanceService $danceService,
    ) {
    }

    public static function getColumns(): string
    {
        $columns = [
            VopisNazinaColumns::SUBJECT,
            VopisNazinaColumns::SIDE,
            VopisNazinaColumns::YEAR,
            VopisNazinaColumns::NOTES,
            VopisNazinaColumns::TITLE,
            VopisNazinaColumns::ADDITIONAL,
            VopisNazinaColumns::PLACE,
            VopisNazinaColumns::INFORMANTS,
            VopisNazinaColumns::USER,
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
        $keyFile = -1;
        $isPrevSubject = false;
        $isPrevFile = false;
        $dateSubject = null;

        $csv = Reader::fromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $key => $record) {
            $nameSubject = trim($record[VopisNazinaColumns::SUBJECT]);
            $side = trim($record[VopisNazinaColumns::SIDE]);
            $title = trim($record[VopisNazinaColumns::TITLE]);
            $sideNotes = TextHelper::replaceLetters($record[VopisNazinaColumns::NOTES]);
            $informants = trim($record[VopisNazinaColumns::INFORMANTS]);
            if ($nameSubject === '' && $title === '' && $side === '' && $sideNotes === '' && $informants === '') {
                continue;
            }

            $dateText = trim($record[VopisNazinaColumns::YEAR]);
            $userText = trim($record[VopisNazinaColumns::USER]);
            $notes = trim($record[VopisNazinaColumns::ADDITIONAL]);

            if (
                $keySubject < 0
                || ($title === '' && $notes === '')
            ) {
                $date = DateHelper::getYear($dateText);
                if (empty($date) && !empty($nameSubject)) {
                    $date = DateHelper::getYear($nameSubject);
                }
                if (!empty($date)) {
                    $dateSubject = $date;
                }

                if (empty($nameSubject) && empty($side)) {
                    if (!isset($subjects[$keySubject], $subjects[$keySubject]->files[$keyFile])) {
                        throw new \Exception(sprintf(
                            'Subject for sideNotes not found (%d, %d) row #%d: %s',
                            $keySubject,
                            $keyFile,
                            $key,
                            $title
                        ));
                    }
                    $note = TextHelper::replaceLetters($sideNotes);
                    $notes = $subjects[$keySubject]->files[$keyFile]->notes;
                    $notes .= (empty($notes) ? '' : "\n") . $note;
                    $subjects[$keySubject]->files[$keyFile]->notes = $notes;
                } elseif (empty($nameSubject)) {
                    if (!isset($subjects[$keySubject])) {
                        throw new \Exception(sprintf(
                            'Subject for side not found (%d, %d) row #%d: %s',
                            $keySubject,
                            $keyFile,
                            $key,
                            $title
                        ));
                    }
                    if (!$isPrevFile) {
                        $keyFile++;
                        $name = TextHelper::replaceLetters($side);
                        $subjects[$keySubject]->files[$keyFile] = new FileDto($name);
                        $subjects[$keySubject]->files[$keyFile]->type = FileType::TYPE_AUDIO;
                        $isPrevFile = true;
                    } else {
                        $note = TextHelper::replaceLetters($side);
                        $notes = $subjects[$keySubject]->files[$keyFile]->notes;
                        $notes .= (empty($notes) ? '' : "\n") . $note;
                        $subjects[$keySubject]->files[$keyFile]->notes = $notes;
                    }
                } elseif (!$isPrevSubject) {
                    if ($keySubject < 0 || $subjects[$keySubject]->name !== $nameSubject) {
                        $keySubject++;
                        $subjects[$keySubject] = new SubjectDto();
                        $subjects[$keySubject]->type = SubjectType::TYPE_AUDIO;
                        $subjects[$keySubject]->name = $nameSubject;
                        $keyFile = -1;
                    }
                    $isPrevSubject = true;
                    $isPrevFile = false;

                    if (empty($date)) {
                        $dateSubject = null;
                    }
                } else {
                    if (!isset($subjects[$keySubject])) {
                        throw new \Exception(sprintf(
                            'Subject for notes not found (%d, %d) row #%d: %s',
                            $keySubject,
                            $keyFile,
                            $key,
                            $title
                        ));
                    }
                    $note = TextHelper::replaceLetters($nameSubject);
                    $notes = $subjects[$keySubject]->notes;
                    $notes .= (empty($notes) ? '' : "\n") . $note;
                    $subjects[$keySubject]->notes = $notes;
                }
            } else {
                $isPrevSubject = false;
                $isPrevFile = false;
                if (!isset($subjects[$keySubject])) {
                    throw new \Exception(sprintf(
                        'Subject not found (%d, %d) row #%d: %s',
                        $keySubject,
                        $keyFile,
                        $key,
                        $title
                    ));
                }
                if (!isset($subjects[$keySubject]->files[$keyFile])) {
                    $keyFile++;
                    $subjects[$keySubject]->files[$keyFile] = new FileDto('-');
                    $subjects[$keySubject]->files[$keyFile]->type = FileType::TYPE_AUDIO;
                }

                $marker = new FileMarkerDto();

                if (!empty($date)) {
                    $dateSubject = $date;
                }
                if (!empty($dateSubject)) {
                    $marker->dateAction = $dateSubject;
                }

                $title = TextHelper::removeFirstNumbers($title);

                $category = $this->categoryService->detectCategory($title, $notes) ?? CategoryType::OTHER;
                $marker->category = $category !== CategoryType::OTHER ? $category : CategoryType::STORY;
                if (!CategoryType::isSystemType($category)) {
                    $marker->name = $title;
                    $marker->notes = $notes;
                }

                $marker->dance = $this->danceService->detectDance($title);

                $marker->userText = $userText;

                $locationText = '';
                $marker->geoPoint = $this->locationService->parsePlace(
                    TextHelper::replaceLetters($record[VopisNazinaColumns::PLACE]),
                    null,
                    null,
                    $locationText
                );
                if (null === $marker->geoPoint) {
                    $marker->place = $locationText;
                }

                $marker->informantsText = TextHelper::replaceLetters($informants);
                $subjects[$keySubject]->files[$keyFile]->markers[] = $marker;
            }
        }

        return $subjects;
    }
}
