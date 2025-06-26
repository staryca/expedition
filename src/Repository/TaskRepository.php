<?php

namespace App\Repository;

use App\Entity\GeoPoint;
use App\Entity\Task;
use App\Entity\Type\TaskStatus;
use App\Service\LocationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @param GeoPoint $geoPoint
     * @return array<Task>
     */
    public function findByReportGeoPoint(GeoPoint $geoPoint): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.report', 'r1')
            ->leftJoin('t.reportBlock', 'rb')
            ->leftJoin('rb.report', 'r2')
            ->where('r1.geoPoint = :geoPoint')
            ->orWhere('r2.geoPoint = :geoPoint')
            ->setParameter('geoPoint', $geoPoint)
            ->orderBy('t.status', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param GeoPoint $geoPoint
     * @return array<Task>
     */
    public function findByInformantGeoPoint(GeoPoint $geoPoint): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.informant', 'i')
            ->leftJoin('i.geoPointCurrent', 'gpCurrent')
            ->where('gpCurrent.lat between :minLat and :maxLat')
            ->andWhere('gpCurrent.lon between :minLon and :maxLon')
            ->andWhere('t.status = :status')
            ->setParameter('minLat', $geoPoint->getLat() - LocationService::LAT_RANGE_UP)
            ->setParameter('maxLat', $geoPoint->getLat() + LocationService::LAT_RANGE_DOWN)
            ->setParameter('minLon', $geoPoint->getLon() - LocationService::LON_RANGE_UP)
            ->setParameter('maxLon', $geoPoint->getLon() + LocationService::LON_RANGE_DOWN)
            ->setParameter('status', TaskStatus::TIP)
            ->getQuery()
            ->getResult();
    }
}
