<?php

declare(strict_types=1);

namespace App\Handler;

use App\Repository\GeoPointRepository;
use Doctrine\ORM\EntityManagerInterface;

class GeoPointHandler
{
    public function __construct(
        private readonly GeoPointRepository $geoPointRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function setRegionsAndDistricts(): array
    {
        $points = $this->geoPointRepository->findWithoutDistrict(1000);

        $data = [];
        foreach ($points as $point) {
            $neighbors = $this->geoPointRepository->findNeighbors($point->getLatLonDto());
            $amounts = ['regions' => [], 'districts' => [], 'prefixes' => []];
            $count = 0;
            foreach ($neighbors as $neighbor) {
                $region = $neighbor->getRegion();
                if (!empty($region)) {
                    if (!isset($amounts['regions'][$region])) {
                        $amounts['regions'][$region] = 1;
                    } else {
                        $amounts['regions'][$region]++;
                    }
                }

                $district = $neighbor->getDistrict();
                if (!empty($district) && $district !== '-') {
                    if (!isset($amounts['districts'][$district])) {
                        $amounts['districts'][$district] = 1;
                    } else {
                        $amounts['districts'][$district]++;
                    }
                }

                $prefix = $neighbor->getPrefixBe();
                if (!empty($prefix)) {
                    if (!isset($amounts['prefixes'][$prefix])) {
                        $amounts['prefixes'][$prefix] = 1;
                    } else {
                        $amounts['prefixes'][$prefix]++;
                    }
                }

                $count++;
            }

            $error = '';

            $newRegion = '';
            if (count($amounts['regions']) === 1) {
                $newRegion = key($amounts['regions']);
                if ($count > 20 && (current($amounts['regions']) > $count / 3) && $point->getRegion() !== $newRegion) {
                    if (!empty($point->getRegion())) {
                        $error = ' Old: ' . $point->getRegion() . ', new: ' . $newRegion;
                    } else {
                        $point->setRegion($newRegion);
                    }
                } else {
                    $newRegion = '';
                }
            }

            $newDistrict = '';
            if (count($amounts['districts']) === 1 && count($amounts['regions']) === 1) {
                $newDistrict = key($amounts['districts']);
                if ($count > 20 && (current($amounts['districts']) > $count / 3) && $point->getDistrict() !== $newDistrict) {
                    if (!empty($point->getDistrict())) {
                        $error = ' Old: ' . $point->getDistrict() . ', new: ' . $newDistrict;
                    } else {
                        $point->setDistrict($newDistrict);
                    }
                } else {
                    $newDistrict = '';
                }
            }

            if (empty($newRegion) && empty($newDistrict)) {
                $point->setDistrict('-');
            }

            $data[] = [
                'id' => $point->getId(),
                'name' => $point->getName(),
                'prefix' => $point->getPrefixBe(),
                'count' => $count,
                'regions' => var_export($amounts['regions'], true),
                'districts' => var_export($amounts['districts'], true),
                'prefixes' => var_export($amounts['prefixes'], true),
                'new' => $newRegion . ' ' . $newDistrict,
                'error' => $error,
            ];
        }

        $this->entityManager->flush();

        return $data;
    }
}
