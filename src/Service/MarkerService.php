<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Expedition;
use App\Entity\FileMarker;
use App\Entity\GeoPoint;
use App\Entity\Type\CategoryType;
use App\Helper\TextHelper;
use App\Repository\FileMarkerRepository;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;

class MarkerService
{
    public function __construct(
        private FileMarkerRepository $fileMarkerRepository,
    ) {
    }

    public function getGroupedMarkersByExpedition(Expedition $expedition): array
    {
        $markers = $this->fileMarkerRepository->getMarkersByExpedition($expedition);

        return self::groupMarkersByCategory($markers);
    }

    public function getGroupedMarkersByGeoPoint(GeoPoint $geoPoint): array
    {
        $markers = $this->fileMarkerRepository->getMarkersByGeoPoint($geoPoint);

        return self::groupMarkersByCategory($markers);
    }

    public function getGroupedMarkersNearGeoPoint(GeoPoint $geoPoint): array
    {
        $markers = $this->fileMarkerRepository->getMarkersNearGeoPoint($geoPoint->getLatLonDto());

        return self::groupMarkersByCategory($markers);
    }

    /**
     * @param array<FileMarker> $markers
     * @return array<int, array<FileMarker>>
     */
    private static function groupMarkersByCategory(array $markers): array
    {
        $result = [];
        foreach ($markers as $marker) {
            $category = $marker->getCategory();
            $result[$category][] = $marker;
        }

        return $result;
    }

    /**
     * @param array<FileMarker> $markers
     * @return array<string, array<FileMarker>>
     */
    private static function groupMarkersByNotes(array $markers, ?int $category = null): array
    {
        $result = [];
        foreach ($markers as $marker) {
            $notes = $marker->getNotes();
            $notes = mb_strtolower((string) $notes);
            $notes = str_replace(['інф.:', '?', 'няпоўная'], '', $notes);
            [$notes] = TextHelper::getNotes($notes);

            $parts = TextHelper::explodeWithBrackets(['"', '“', '.', ','], $notes);
            $notes = (trim($parts[0]) === '' && isset($parts[1])) ? $parts[1] : $parts[0];

            if ($category !== null) {
                $notes = str_replace(
                    [mb_strtolower(CategoryType::getSingleName($category)), mb_strtolower(CategoryType::getManyOrSingleName($category))],
                    '',
                    $notes
                );
            }
            $notes = mb_ucfirst(trim($notes));
            if ($notes === '') {
                $notes = '(нявызначаныя)';
            }
            $result[$notes][] = $marker;
        }

        ksort($result);

        return $result;
    }

    public function getSongsNearGeoPoint(GeoPoint $geoPoint): array
    {
        $category = CategoryType::SONGS;
        $markers = $this->fileMarkerRepository->getMarkersNearGeoPoint($geoPoint->getLatLonDto(), $category);

        return self::groupMarkersByNotes($markers, $category);
    }

    /**
     * @param array<string, array<FileMarker>> $markerGroups
     * @return Writer
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function generateCsvFromMarkers(array $markerGroups): Writer
    {
        $csv = Writer::createFromString();
        $csv->setDelimiter(';');

        $csv->insertOne(['Назва', 'Месца', 'Дадаткова', 'Крыніца']);

        foreach ($markerGroups as $groupName => $markers) {
            $csv->insertOne([$groupName, '', '', '']);

            foreach ($markers as $marker) {
                $year = $marker->getReport()?->getDateActionYear();

                $csv->insertOne([
                    (string) $marker->getName(),
                    (string) $marker->getReport()?->getGeoPoint()?->getName(),
                    (string) $marker->getNotes(),
                    ((!empty($year)) ? $year . ', ' : '') . $marker->getReport()?->getExpedition()->getShortName()
                ]);
            }
        }

        return $csv;
    }
}
