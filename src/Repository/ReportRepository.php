<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Expedition;
use App\Entity\GeoPoint;
use App\Entity\Report;
use App\Service\LocationService;
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
            ->andWhere('r.temp IS NULL ')
            ->setParameter('val', $expedition)
            ->orderBy('r.dateAction', 'ASC')
            ->addOrderBy('r.geoPoint', 'ASC')
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
     * @return array<Report>
     */
    public function findNearGeoPoint(GeoPoint $geoPoint): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.geoPoint', 'gp')
            ->where('gp.lat between :minLat and :maxLat')
            ->andWhere('gp.lon between :minLon and :maxLon')
            ->setParameter('minLat', $geoPoint->getLat() - LocationService::LAT_RANGE_UP)
            ->setParameter('maxLat', $geoPoint->getLat() + LocationService::LAT_RANGE_DOWN)
            ->setParameter('minLon', $geoPoint->getLon() - LocationService::LON_RANGE_UP)
            ->setParameter('maxLon', $geoPoint->getLon() + LocationService::LON_RANGE_DOWN)
            ->getQuery()
            ->getResult();
    }
}
