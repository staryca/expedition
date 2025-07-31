<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GeoPoint;
use App\Entity\Informant;
use App\Entity\Task;
use App\Entity\Type\TaskStatus;
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
     * @param GeoPoint $geoPoint
     * @param float|null $radius
     * @return array<Informant>
     */
    public function findNearCurrentGeoPoint(GeoPoint $geoPoint, ?float $radius = null): array
    {
        $latUp = $radius ?? LocationService::LAT_RANGE_UP;
        $latDown = $radius ?? LocationService::LAT_RANGE_DOWN;
        $lonUp = $radius ?? LocationService::LON_RANGE_UP;
        $lonDown = $radius ?? LocationService::LON_RANGE_DOWN;

        return $this->createQueryBuilder('i')
            ->leftJoin('i.geoPointCurrent', 'gpCurrent')
            ->where('gpCurrent.lat between :minLat and :maxLat')
            ->andWhere('gpCurrent.lon between :minLon and :maxLon')
            ->setParameter('minLat', $geoPoint->getLat() - $latUp)
            ->setParameter('maxLat', $geoPoint->getLat() + $latDown)
            ->setParameter('minLon', $geoPoint->getLon() - $lonUp)
            ->setParameter('maxLon', $geoPoint->getLon() + $lonDown)
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
