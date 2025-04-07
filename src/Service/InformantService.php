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

        $groupedByName = [];

        foreach ($informants as $informant) {
            if ($informant instanceof Informant) {
                $firstName = $informant->getFirstName();
                if (!isset($groupedByName[$firstName])) {
                    $groupedByName[$firstName] = [];
                }
                $groupedByName[$firstName][] = $informant;
            }
        }

        foreach ($groupedByName as $group) {
            $count = count($group);
            if ($count > 1) {
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $informant1 = $group[$i];
                        $informant2 = $group[$j];

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