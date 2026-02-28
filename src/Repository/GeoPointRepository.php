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

        $withEmpty = !empty($geoPointSearchDto->names);

        if ($geoPointSearchDto->names) {
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
                $comp = $qb->expr()->like('gp.district', ':district');
            } else {
                $district = $geoPointSearchDto->district;
                $comp = 'gp.district = :district';
            }
            $exReg = !$withEmpty ? $comp : $qb->expr()->orX($comp, 'LENGTH(gp.district) = 0');
            $qb->andWhere($exReg)
                ->setParameter('district', $district);
        }

        if (null !== $geoPointSearchDto->subDistrict) {
            $comp = 'gp.subdistrict = :subDistrict';
            $exReg = !$withEmpty ? $comp : $qb->expr()->orX($comp, 'LENGTH(gp.subdistrict) = 0');
            $qb->andWhere($exReg)
                ->setParameter('subDistrict', $geoPointSearchDto->subDistrict);
        }

        if (null !== $geoPointSearchDto->region) {
            $comp = 'gp.region = :region';
            $exReg = !$withEmpty ? $comp : $qb->expr()->orX($comp, 'LENGTH(gp.region) = 0');
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
     * @param GeoPoint $geoPoint
     * @param float|null $radius
     * @param bool $withTract
     * @return array<GeoPoint>
     */
    public function findNotFarFromPoint(GeoPoint $geoPoint, ?float $radius = null, bool $withTract = false): array
    {
        $latUp = $radius ?? LocationService::POINT_RADIUS;
        $latDown = $radius ?? LocationService::POINT_RADIUS;
        $lonUp = $radius ?? LocationService::POINT_RADIUS * LocationService::LAT_LON_RATE;
        $lonDown = $radius ?? LocationService::POINT_RADIUS * LocationService::LAT_LON_RATE;

        $qb = $this->createQueryBuilder('gp')
            ->where('gp.lat between :minLat and :maxLat')
            ->andWhere('gp.lon between :minLon and :maxLon')
            ->setParameter('minLat', $geoPoint->getLat() - $latUp)
            ->setParameter('maxLat', $geoPoint->getLat() + $latDown)
            ->setParameter('minLon', $geoPoint->getLon() - $lonUp)
            ->setParameter('maxLon', $geoPoint->getLon() + $lonDown);

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
            ->setParameter('minLon', $dto->lon - LocationService::POINT_NEIGHBOR * LocationService::LAT_LON_RATE)
            ->setParameter('maxLon', $dto->lon + LocationService::POINT_NEIGHBOR * LocationService::LAT_LON_RATE);

        return $qb
            ->getQuery()
            ->getResult();
    }
}
