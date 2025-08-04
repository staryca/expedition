<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\GeoPoint;
use App\Entity\Informant;
use App\Repository\InformantRepository;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;

class InformantService
{
    public function __construct(
        private readonly InformantRepository $informantRepository,
    ) {
    }

    /**
     * @param GeoPoint $geoPoint
     * @param float|null $radius
     * @return array<string, array<Informant>
     */
    public function getInformantsNearGeoPoint(GeoPoint $geoPoint, ?float $radius = null): array
    {
        $result = [];

        $informants = $this->informantRepository->findNearCurrentGeoPoint($geoPoint, $radius);
        foreach ($informants as $informant) {
            $location = $informant->getGeoPointCurrent()?->getFullBeName();
            if (empty($location)) {
                $location = $informant->getPlaceCurrent();
            }
            if (empty($location)) {
                $location = $informant->getGeoPointBirth()?->getFullBeName();
            }
            if (empty($location)) {
                $location = $informant->getPlaceBirth();
            }
            if (empty($location)) {
                $location = '[невядома]';
            }

            $result[$location][] = $informant;
        }

        return $result;
    }

    /**
     * @param array<string, array<Informant>> $informantsByLocation
     * @return Writer
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function generateCsvFromInformants(array $informantsByLocation): Writer
    {
        $csv = Writer::createFromString();
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');

        $csv->insertOne(['Імя', 'Нарадзіўся', 'Жыве', 'Дадаткова']);

        foreach ($informantsByLocation as $location => $informants) {
            $csv->insertOne([$location, '', '']);

            foreach ($informants as $informant) {
                $csv->insertOne([
                    $informant->getFirstName() . ($informant->getYearBirth() ? ', ' . $informant->getYearBirth() . ' г.н.' : ''),
                    empty($informant->getBirthPlaceBe()) ? ' ' : $informant->getBirthPlaceBe(),
                    empty($informant->getCurrentPlaceBe()) ? ' ' : $informant->getCurrentPlaceBe(),
                    $informant->getNotes()
                ]);
            }
        }

        return $csv;
    }
}
