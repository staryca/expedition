<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\FileDto;
use App\Dto\FileMarkerDto;
use App\Entity\Type\CategoryType;
use App\Entity\Type\FileType;
use App\Parser\Columns\MapColumns;
use App\Service\LocationService;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;

readonly class MapParser
{
    public function __construct(
        private LocationService $locationService,
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
     * @return array<FileDto>
     * @throws InvalidArgument
     * @throws Exception
     */
    public function parse(string $content): array
    {
        $file = new FileDto("");
        $file->type = FileType::TYPE_OTHER;

        $csv = Reader::fromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $record) {
            $marker = new FileMarkerDto();
            $marker->isNewBlock = true;
            $marker->category = CategoryType::OTHER;

            $subDistrict = self::getValue($record, MapColumns::SOVIET);
            $location = $this->locationService->detectLocation(
                self::getValue($record, MapColumns::VILLAGE),
                self::getValue($record, MapColumns::DISTINCT) . ' ' . LocationService::DISTRICT,
                empty($subDistrict) ? null : $subDistrict . ' ' . LocationService::SUBDISTRICT
            );
            $geoPointId = self::getValue($record, MapColumns::MAP_INDEX);
            if ($location && (!$geoPointId || $location->getId() === $geoPointId)) {
                $marker->geoPoint = $location;
            } else {
                $marker->place =
                    self::getValue($record, MapColumns::VILLAGE) . ', '
                    . self::getValue($record, MapColumns::DISTINCT) . ' ' . LocationService::DISTRICT . ', '
                    . (empty($subDistrict) ? '' : ($subDistrict . ' ' . LocationService::SUBDISTRICT))
                ;
            }

            $marker->additional['color'] = self::getValue($record, MapColumns::COLOR);

            $file->markers[] = $marker;
        }

        return [$file];
    }
}
