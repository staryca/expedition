<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Expedition;
use App\Entity\GeoPoint;
use App\Entity\Report;
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
}
