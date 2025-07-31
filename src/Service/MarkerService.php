<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Expedition;
use App\Entity\GeoPoint;
use App\Repository\FileMarkerRepository;

class MarkerService
{
    public function __construct(
        private FileMarkerRepository $fileMarkerRepository,
    ) {
    }

    public function getGroupedMarkersByExpedition(Expedition $expedition): array
    {
        $markers = $this->fileMarkerRepository->getMarkersByExpedition($expedition);

        return self::groupMarkers($markers);
    }

    public function getGroupedMarkersByGeoPoint(GeoPoint $geoPoint): array
    {
        $markers = $this->fileMarkerRepository->getMarkersByGeoPoint($geoPoint);

        return self::groupMarkers($markers);
    }

    public function getGroupedMarkersNearGeoPoint(GeoPoint $geoPoint): array
    {
        $markers = $this->fileMarkerRepository->getMarkersNearGeoPoint($geoPoint->getLatLonDto());

        return self::groupMarkers($markers);
    }

    private static function groupMarkers(array $markers): array
    {
        $result = [];
        foreach ($markers as $marker) {
            $category = $marker->getCategory();
            $result[$category][] = $marker;
        }

        return $result;
    }
}
