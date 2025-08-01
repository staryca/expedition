<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Expedition;
use App\Entity\GeoPoint;
use App\Entity\Report;
use App\Service\LocationService;
use App\Service\ReportService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Report>
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    /**
     * @return array<Report>
     */
    public function findByExpedition(Expedition $expedition): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.expedition = :val')
            ->setParameter('val', $expedition)
            ->orderBy('r.dateAction', 'ASC')
            ->addOrderBy('r.geoPoint', 'ASC')
            ->setMaxResults(ReportService::MAX_REPORTS_FOR_VIEW)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Report>
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.temp IS NULL ')
            ->orderBy('r.dateAction', 'ASC')
            ->addOrderBy('r.geoPoint', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Report>
     */
    public function findByGeoPoint(GeoPoint $geoPoint): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.geoPoint = :geoPoint')
            ->andWhere('r.temp IS NULL ')
            ->setParameter('geoPoint', $geoPoint)
            ->orderBy('r.dateAction', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param GeoPoint $geoPoint
     * @param float|null $radius
     * @return array<Report>
     */
    public function findNearGeoPoint(GeoPoint $geoPoint, ?float $radius = null): array
    {
        $latUp = $radius ?? LocationService::LAT_RANGE_UP;
        $latDown = $radius ?? LocationService::LAT_RANGE_DOWN;
        $lonUp = $radius ?? LocationService::LON_RANGE_UP;
        $lonDown = $radius ?? LocationService::LON_RANGE_DOWN;

        return $this->createQueryBuilder('r')
            ->leftJoin('r.geoPoint', 'gp')
            ->where('gp.lat between :minLat and :maxLat')
            ->andWhere('gp.lon between :minLon and :maxLon')
            ->setParameter('minLat', $geoPoint->getLat() - $latUp)
            ->setParameter('maxLat', $geoPoint->getLat() + $latDown)
            ->setParameter('minLon', $geoPoint->getLon() - $lonUp)
            ->setParameter('maxLon', $geoPoint->getLon() + $lonDown)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Report>
     */
    public function findNotDetectedPoints(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.geoPoint IS NULL')
            ->andWhere('r.geoNotes <> :empty')
            ->setParameter('empty', '')
            ->orderBy('r.geoNotes', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
