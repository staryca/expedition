<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\GeoPointSearchDto;
use App\Dto\LatLonDto;
use App\Entity\GeoPoint;
use App\Entity\Type\GeoPointType;
use App\Service\LocationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GeoPoint>
 */
class GeoPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeoPoint::class);
    }

    /**
     * @return array<GeoPoint>
     */
    public function findByNameAndDistrict(GeoPointSearchDto $geoPointSearchDto): array
    {
        $qb = $this->createQueryBuilder('gp');

        if ($geoPointSearchDto->prefixes) {
            $qb->andWhere('gp.name IN (:names)')
                ->setParameter('names', $geoPointSearchDto->names);
        }

        if ($geoPointSearchDto->prefixes) {
            $qb->andWhere('gp.prefixBe IN (:prefixes)')
                ->setParameter('prefixes', $geoPointSearchDto->prefixes);
        }

        if (null !== $geoPointSearchDto->district) {
            if (str_contains($geoPointSearchDto->district, '.')) {
                $district = str_replace('.', '%', $geoPointSearchDto->district);
                $exReg = $qb->expr()->orX(
                    $qb->expr()->like('gp.district', ':district'),
                    'LENGTH(gp.district) = 0'
                );
            } else {
                $district = $geoPointSearchDto->district;
                $exReg = $qb->expr()->orX('gp.district = :district', 'LENGTH(gp.district) = 0');
            }
            $qb->andWhere($exReg)
                ->setParameter('district', $district);
        }

        if (null !== $geoPointSearchDto->subDistrict) {
            $exReg = $qb->expr()->orX('gp.subdistrict = :subDistrict', 'LENGTH(gp.subdistrict) = 0');
            $qb->andWhere($exReg)
                ->setParameter('subDistrict', $geoPointSearchDto->subDistrict);
        }

        if (null !== $geoPointSearchDto->region) {
            $exReg = $qb->expr()->orX('gp.region = :region', 'LENGTH(gp.region) = 0');
            $qb->andWhere($exReg)
                ->setParameter('region', $geoPointSearchDto->region);
        }

        if (null !== $geoPointSearchDto->limit) {
            $qb->setMaxResults($geoPointSearchDto->limit);
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param GeoPoint $getGeoPoint
     * @param bool $withTract
     * @return array<GeoPoint>
     */
    public function findNotFarFromPoint(GeoPoint $getGeoPoint, bool $withTract = false): array
    {
        $qb = $this->createQueryBuilder('gp')
            ->where('gp.lat between :minLat and :maxLat')
            ->andWhere('gp.lon between :minLon and :maxLon')
            ->setParameter('minLat', $getGeoPoint->getLat() - LocationService::LAT_RANGE_UP)
            ->setParameter('maxLat', $getGeoPoint->getLat() + LocationService::LAT_RANGE_DOWN)
            ->setParameter('minLon', $getGeoPoint->getLon() - LocationService::LON_RANGE_UP)
            ->setParameter('maxLon', $getGeoPoint->getLon() + LocationService::LON_RANGE_DOWN);

        $qb->andWhere('gp.prefixBe != :prefixSnp')
            ->setParameter('prefixSnp', GeoPointType::BE_SNP);

        if (!$withTract) {
            $qb->andWhere('gp.prefixBe != :prefixTract')
                ->setParameter('prefixTract', GeoPointType::BE_TRACT);
        }

        return $qb
            ->orderBy('gp.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<GeoPoint>
     */
    public function findWithoutDistrict(int $amount): array
    {
        return $this->createQueryBuilder('gp')
            ->where('gp.district = :empty')
            ->setParameter('empty', '')
            ->orderBy('RANDOM()')
            ->setMaxResults($amount)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param LatLonDto $dto
     * @return array<GeoPoint>
     */
    public function findNeighbors(LatLonDto $dto): array
    {
        $qb = $this->createQueryBuilder('gp')
            ->where('gp.lat between :minLat and :maxLat')
            ->andWhere('gp.lon between :minLon and :maxLon')
            ->setParameter('minLat', $dto->lat - LocationService::POINT_NEIGHBOR)
            ->setParameter('maxLat', $dto->lat + LocationService::POINT_NEIGHBOR)
            ->setParameter('minLon', $dto->lon - LocationService::POINT_NEIGHBOR)
            ->setParameter('maxLon', $dto->lon + LocationService::POINT_NEIGHBOR);

        return $qb
            ->getQuery()
            ->getResult();
    }
}
