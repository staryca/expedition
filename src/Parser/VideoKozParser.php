<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\FileDto;
use App\Dto\VideoItemDto;
use App\Entity\Type\CategoryType;
use App\Entity\Type\FileType;
use App\Helper\TextHelper;
use App\Parser\Columns\VideoKozColumns;
use App\Repository\PackRepository;
use App\Service\LocationService;
use App\Service\PersonService;
use Carbon\Carbon;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;

class VideoKozParser
{
    public function __construct(
        private readonly LocationService $locationService,
        private readonly PersonService   $personService,
        private readonly TextHelper      $textHelper,
        private readonly PackRepository  $packRepository,
     ) {
    }

    /**
     * @param string $content
     * @return FileDto[]
     * @throws Exception
     * @throws InvalidArgument
     */
    public function parse(string $content): array
    {
        /** @var array<FileDto> $files */
        $files = [];
        $key = -1;

        $csv = Reader::createFromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $record) {
            $filename = $record[VideoKozColumns::FILENAME];
            [$filename] = $this->textHelper->getNotes($filename);
            if ($filename === '') {
                continue;
            }
            if ($record[VideoKozColumns::VISIBLE] === '0') {
                continue;
            }

            if ($key < 0 || $files[$key]->getFilename() !== $filename) {
                $key++;
                $files[$key] = new FileDto($filename);
                $files[$key]->type = FileType::TYPE_VIDEO;
            }

            $videoDto = new VideoItemDto();
            $videoDto->category = CategoryType::findId($record[VideoKozColumns::TYPE_RECORD], '');
            $videoDto->baseName = $record[VideoKozColumns::BASE_NAME] ?? null;
            $videoDto->localName = $record[VideoKozColumns::LOCAL_NAME] ?? null;
            $videoDto->pack = $this->packRepository->getPackByName(
                $record[VideoKozColumns::TYPE_DANCE] ?? null
            );
            $videoDto->improvisation = $record[VideoKozColumns::IMPROVISATION] ?? null;
            $videoDto->ritual = $record[VideoKozColumns::RITUAL] ?? null;
            $videoDto->notes = $record[VideoKozColumns::DESCRIPTION] ?? null;
            $videoDto->texts = $record[VideoKozColumns::TEXTS] ?? null;
            $videoDto->tmkb = $record[VideoKozColumns::TMKB] ?? null;

            $location = $this->locationService->detectLocation(
                $record[VideoKozColumns::VILLAGE],
                $record[VideoKozColumns::DISTINCT] . ' ' . LocationService::DISTRICT,
                $record[VideoKozColumns::SOVIET] . ' ' . LocationService::SUBDISTRICT
            );
            $geoPointId = $record[VideoKozColumns::MAP_INDEX] ?? null;
            if ($location && (!$geoPointId || $location->getId() === $geoPointId)) {
                $videoDto->geoPoint = $location;
            } else {
                $videoDto->place =
                    $record[VideoKozColumns::VILLAGE] . ', '
                    . $record[VideoKozColumns::DISTINCT] . ' ' . LocationService::DISTRICT . ', '
                    . $record[VideoKozColumns::SOVIET] . ' ' . LocationService::SUBDISTRICT
                ;
            }

            if (isset($record[VideoKozColumns::ORGANIZATION])) {
                $videoDto->organizationName = trim($record[VideoKozColumns::ORGANIZATION]);
            }
            if (isset($record[VideoKozColumns::INFORMANTS])) {
                $videoDto->informants = $this->personService->getInformants($record[VideoKozColumns::INFORMANTS]);
            }

            $dateAction = $record[VideoKozColumns::DATE_RECORD] ?? null;
            if (!empty(trim($dateAction))) {
                if ($dateAction[0] === '(') {
                    $videoDto->notes .=
                        (empty($videoDto->notes) ? '' : "\n\r")
                        . 'Дата запісу: ' . trim(str_replace(['(', ')'], '', $dateAction))
                    ;
                } elseif (strlen($dateAction) < 5) {
                    $videoDto->dateAction = Carbon::createFromDate((int) $dateAction, 1, 1);
                } else {
                    $videoDto->dateAction = Carbon::createFromFormat('d.m.Y', $dateAction);
                }
            }

            $files[$key]->videoItems[] = $videoDto;
        }

        return $files;
    }
}
