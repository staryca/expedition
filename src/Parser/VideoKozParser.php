<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\FileDto;
use App\Dto\VideoItemDto;
use App\Entity\Additional\FileMarkerAdditional;
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

readonly class VideoKozParser
{
    public function __construct(
        private LocationService $locationService,
        private PersonService $personService,
        private PackRepository $packRepository,
    ) {
    }

    private static function getValue(array $array, string $key): ?string
    {
        if (array_key_exists($key, $array)) {
            return trim($array[$key]);
        }

        return null;
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
        $count = 0;

        $csv = Reader::fromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $record) {
            $count++;
            $filename = $record[VideoKozColumns::FILENAME];
            [$filename] = TextHelper::getNotes($filename);
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
            $number = self::getValue($record, VideoKozColumns::NUMBER);
            $videoDto->number = empty($number) ? null : (int) $number;

            $videoDto->category = CategoryType::findId($record[VideoKozColumns::TYPE_RECORD], '');
            $videoDto->baseName = self::getValue($record, VideoKozColumns::BASE_NAME);
            $videoDto->localName = $record[VideoKozColumns::LOCAL_NAME] ?? null;
            $videoDto->youTube = $this->getYoutube(self::getValue($record, VideoKozColumns::YOUTUBE));
            $videoDto->pack = $this->packRepository->getPackByName(
                self::getValue($record, VideoKozColumns::TYPE_DANCE)
            );
            $videoDto->improvisation = FileMarkerAdditional::getImprovisation(
                self::getValue($record, VideoKozColumns::IMPROVISATION)
            );
            $videoDto->ritual = self::getValue($record, VideoKozColumns::RITUAL);
            $videoDto->tradition = self::getValue($record, VideoKozColumns::TRADITION);
            $videoDto->notes = self::getValue($record, VideoKozColumns::DESCRIPTION);
            $videoDto->source = self::getValue($record, VideoKozColumns::SOURCE);
            $videoDto->texts = self::getValue($record, VideoKozColumns::TEXTS);
            $videoDto->tmkb = self::getValue($record, VideoKozColumns::TMKB);

            $subDistrict = self::getValue($record, VideoKozColumns::SOVIET);
            $village = self::getValue($record, VideoKozColumns::VILLAGE);
            if (str_contains($village, '?')) {
                $village = '';
            }

            $locationText = '';
            $location = $this->locationService->parsePlace(
                $village,
                self::getValue($record, VideoKozColumns::DISTINCT),
                empty($subDistrict) ? null : $subDistrict . ' ' . LocationService::SUBDISTRICT,
                $locationText
            );
            $geoPointId = self::getValue($record, VideoKozColumns::MAP_INDEX);
            if ($location && !empty($geoPointId) && $location->getId() !== $geoPointId) {
                throw new \Exception(sprintf(
                    'Bad location "%s" and Map index "%s", row #%d: %s',
                    $location->getFullBeName(),
                    $geoPointId,
                    $count,
                    implode(';', $record)
                ));
            }
            if ($location) {
                $videoDto->geoPoint = $location;
            } else {
                $videoDto->place = $locationText;
            }

            $organizationName = self::getValue($record, VideoKozColumns::ORGANIZATION);
            $videoDto->organizationName = empty($organizationName) ? null : $organizationName;

            if (isset($record[VideoKozColumns::INFORMANTS])) {
                $videoDto->informants = $this->personService->getInformants($record[VideoKozColumns::INFORMANTS], '');
            }
            if (isset($record[VideoKozColumns::MUSICIANS])) {
                $videoDto->informants =
                    [
                        ...$videoDto->informants,
                        ...$this->personService->getInformants($record[VideoKozColumns::MUSICIANS], '', true)
                    ];
            }

            $dateAction = self::getValue($record, VideoKozColumns::DATE_RECORD);
            if (!empty($dateAction)) {
                if ($dateAction[0] === '(') {
                    $dateActionNotes = trim(str_replace(['(', ')'], '', $dateAction));
                    $videoDto->dateActionNotes = $dateActionNotes;
                } elseif (strlen($dateAction) < 5) {
                    $videoDto->dateAction = Carbon::createFromDate((int) $dateAction, 1, 1);
                } else {
                    try {
                        $videoDto->dateAction = Carbon::createFromFormat('d.m.Y', $dateAction);
                    } catch (\Exception $e) {
                        throw new \Exception(sprintf(
                            'Bad date "%s", row #%d: %s',
                            $dateAction,
                            $count,
                            implode(';', $record)
                        ));
                    }
                }
            }

            $files[$key]->videoItems[] = $videoDto;
        }

        return $files;
    }

    private function getYoutube(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        $parts = parse_url($text);

        if (!isset($parts['host'])) {
            return $parts['path'] ?? null;
        }

        if ($parts['host'] === 'www.youtube.com') {
            parse_str($parts['query'], $params);

            return $params['v'] ?? null;
        }

        if ($parts['host'] === 'youtu.be') {
            return mb_substr($parts['path'], 1);
        }

        return null;
    }
}
