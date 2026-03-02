<?php

declare(strict_types=1);

namespace App\Handler;

use App\Repository\GeoPointRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class GeoPointHandler
{
    public function __construct(
        private GeoPointRepository $geoPointRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function setRegionsAndDistricts(): array
    {
        $points = $this->geoPointRepository->findWithoutDistrict(3000);

        $data = [
            0 => [
                'id' => 'Updated',
                'name' => 0,
            ]
        ];
        $updated = 0;
        foreach ($points as $point) {
            $oldRegion = $point->getRegion();
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

            arsort($amounts['regions']);
            $newRegion = count($amounts['regions']) === 1 ? key($amounts['regions']) : '';
            if (count($amounts['regions']) >= 2) {
                $newRegion = key($amounts['regions']);
                $amount = next($amounts['regions']);
                $value = next($amounts['regions']);
                while ($value !== false) {
                    $amount += $value;
                    $value = next($amounts['regions']);
                }
                if (
                    $amounts['regions'][$newRegion] < $count * 0.65
                    || $amount > $count * 0.10
                    || $amounts['regions'][$newRegion] < 16 * $amount
                ) {
                    $newRegion = '';
                }
            }
            if (!empty($newRegion)) {
                if ($count > 20 && ($amounts['regions'][$newRegion] > $count / 3) && $point->getRegion() !== $newRegion) {
                    if (!empty($point->getRegion())) {
                        $error = ' Old: ' . $point->getRegion() . ', new: ' . $newRegion;
                    } else {
                        $point->setRegion($newRegion);
                    }
                } else {
                    $newRegion = '';
                }
            }

            arsort($amounts['districts']);
            $newDistrict = count($amounts['districts']) === 1 ? key($amounts['districts']) : '';
            if (count($amounts['districts']) >= 2) {
                $newDistrict = key($amounts['districts']);
                $amount = next($amounts['districts']);
                $value = next($amounts['districts']);
                while ($value !== false) {
                    $amount += $value;
                    $value = next($amounts['districts']);
                }
                if (
                    $amounts['districts'][$newDistrict] < $count * 0.58
                    || $amount > $count * 0.10
                    || $amounts['districts'][$newDistrict] < 16 * $amount
                ) {
                    $newDistrict = '';
                }
            }
            if (empty($newRegion) && empty($oldRegion)) {
                $newDistrict = '';
            }
            if (!empty($newDistrict)) {
                if ($count > 20 && ($amounts['districts'][$newDistrict] > $count / 3) && $point->getDistrict() !== $newDistrict) {
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
                $point->setDistrict('-'); // for skip
            } else {
                $updated++;
            }

            $link = 'https://www.openstreetmap.org/node/' . $point->getId();
            $data[] = [
                'id' => $point->getId(),
                'name' => $point->getName() . '<br>' . $oldRegion,
                'prefix' => $point->getPrefixBe(),
                'count' => $count,
                'regions' => var_export($amounts['regions'], true),
                'districts' => var_export($amounts['districts'], true),
                'prefixes' => var_export($amounts['prefixes'], true),
                'new' => $newRegion . ' ' . $newDistrict,
                'error' => $error,
                'link' => '<a target="_blank" href="' . $link . '"><i class="bi bi-box-arrow-in-right"></i></a>',
            ];
        }
        $data[0]['name'] = $updated . '/' . count($points);

        $this->entityManager->flush();

        return $data;
    }
}
