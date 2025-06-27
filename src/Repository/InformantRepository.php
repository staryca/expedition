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
     * @return array<Informant>
     */
    public function findNearCurrentGeoPoint(GeoPoint $geoPoint): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.geoPointCurrent', 'gpCurrent')
            ->where('gpCurrent.lat between :minLat and :maxLat')
            ->andWhere('gpCurrent.lon between :minLon and :maxLon')
            ->setParameter('minLat', $geoPoint->getLat() - LocationService::LAT_RANGE_UP)
            ->setParameter('maxLat', $geoPoint->getLat() + LocationService::LAT_RANGE_DOWN)
            ->setParameter('minLon', $geoPoint->getLon() - LocationService::LON_RANGE_UP)
            ->setParameter('maxLon', $geoPoint->getLon() + LocationService::LON_RANGE_DOWN)
            ->getQuery()
            ->getResult();
    }
}
