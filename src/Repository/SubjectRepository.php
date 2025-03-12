<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GeoPoint;
use App\Entity\Subject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subject>
 */
class SubjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subject::class);
    }

    /**
     * @param GeoPoint $geoPoint
     * @return array<Subject>
     */
    public function findByGeoPoint(GeoPoint $geoPoint): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.reportBlock', 'rb')
            ->leftJoin('rb.report', 'r')
            ->andWhere('r.geoPoint = :geoPoint')
            ->setParameter('geoPoint', $geoPoint)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
