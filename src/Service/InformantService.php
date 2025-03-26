<?php


namespace App\Service;


use App\Entity\Informant;

class InformantService
{
    /**
     * @param array<Informant> $informants
     * @return array Array of arrays with pairs of informants
     */
    public function getDuplicates(array $informants): array
    {
        $result = [];

        for ($i = 0; $i < count($informants); $i++) {
            for ($j = $i + 1; $j < count($informants); $j++) {
                $informant1 = $informants[$i];
                $informant2 = $informants[$j];

                if ($informant1 instanceof Informant && $informant2 instanceof Informant) {
                    if ($informant1->getFirstName() === $informant2->getFirstName()) {
                        if (
                            $informant1->getGeoPointBirth() === $informant2->getGeoPointBirth() ||
                            $informant1->getGeoPointCurrent() === $informant2->getGeoPointCurrent()
                        ) {
                            $result[] = [$informant1, $informant2];
                        }
                    }
                }
            }
        }

        return $result;
    }
}