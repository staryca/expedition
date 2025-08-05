<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GeoPoint;
use App\Entity\Informant;
use App\Service\LocationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Informant>
 */
class InformantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Informant::class);
    }

    /**
     * @return array<Informant>
     */
    public function findByGeoPoint(GeoPoint $geoPoint): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.geoPointBirth = :geoPoint')
            ->orWhere('i.geoPointCurrent = :geoPoint')
            ->setParameter('geoPoint', $geoPoint)
            ->orderBy('i.firstName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Informant>
     */
    public function findSortedByName(): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.firstName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param GeoPoint|null $geoPoint
     * @param float|null $radius
     * @param string|null $district
     * @return array<Informant>
     */
    public function findCurrentInLocation(?GeoPoint $geoPoint = null, ?float $radius = null, ?string $district = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.geoPointCurrent', 'gpCurrent');

        if ($geoPoint) {
            $latUp = $radius ?? LocationService::POINT_RADIUS;
            $latDown = $radius ?? LocationService::POINT_RADIUS;
            $lonUp = $radius ?? LocationService::POINT_RADIUS * LocationService::LAT_LON_RATE;
            $lonDown = $radius ?? LocationService::POINT_RADIUS * LocationService::LAT_LON_RATE;

            $qb->where('gpCurrent.lat between :minLat and :maxLat')
                ->andWhere('gpCurrent.lon between :minLon and :maxLon')
                ->setParameter('minLat', $geoPoint->getLat() - $latUp)
                ->setParameter('maxLat', $geoPoint->getLat() + $latDown)
                ->setParameter('minLon', $geoPoint->getLon() - $lonUp)
                ->setParameter('maxLon', $geoPoint->getLon() + $lonDown);
        }

        if ($district) {
            $qb->andWhere($qb->expr()->orX('gpCurrent.district = :district'))
                ->setParameter('district', $district);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Informant>
     */
    public function findNotDetectedPoints(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.placeBirth <> :empty')
            ->orWhere('r.placeCurrent <> :empty')
            ->setParameter('empty', '')
            ->orderBy('r.placeCurrent', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
