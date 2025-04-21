<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GeoPoint;
use App\Entity\Informant;
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
}
