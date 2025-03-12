<?php

namespace App\Repository;

use App\Entity\GeoPoint;
use App\Entity\Task;
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
    public function findByGeoPoint(GeoPoint $geoPoint): array
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
}
