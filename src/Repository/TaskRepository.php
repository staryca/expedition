<?php

declare(strict_types=1);

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
     * @param float|null $radius
     * @return array<Task>
     */
    public function findTipsByInformantGeoPoint(GeoPoint $geoPoint, ?float $radius = null): array
    {
        $latUp = $radius ?? LocationService::POINT_RADIUS;
        $latDown = $radius ?? LocationService::POINT_RADIUS;
        $lonUp = $radius ?? LocationService::POINT_RADIUS * LocationService::LAT_LON_RATE;
        $lonDown = $radius ?? LocationService::POINT_RADIUS * LocationService::LAT_LON_RATE;

        return $this->createQueryBuilder('t')
            ->leftJoin('t.informant', 'i')
            ->leftJoin('i.geoPointCurrent', 'gpCurrent')
            ->where('gpCurrent.lat between :minLat and :maxLat')
            ->andWhere('gpCurrent.lon between :minLon and :maxLon')
            ->andWhere('t.status = :status')
            ->setParameter('minLat', $geoPoint->getLat() - $latUp)
            ->setParameter('maxLat', $geoPoint->getLat() + $latDown)
            ->setParameter('minLon', $geoPoint->getLon() - $lonUp)
            ->setParameter('maxLon', $geoPoint->getLon() + $lonDown)
            ->setParameter('status', TaskStatus::TIP)
            ->getQuery()
            ->getResult();
    }
}
