<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\GeoPointSearchDto;
use App\Entity\GeoPoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GeoPoint>
 */
class GeoPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeoPoint::class);
    }

    /**
     * @return array<GeoPoint>
     */
    public function findByNameAndDistrict(GeoPointSearchDto $geoPointSearchDto): array
    {
        $qb = $this->createQueryBuilder('gp');

        if ($geoPointSearchDto->prefixes) {
            $exReg = $qb->expr()->orX('gp.name IN (:names)', 'gp.nameWordStress IN (:names)');
            $qb->andWhere($exReg)
                ->setParameter('names', $geoPointSearchDto->names);
        }

        if ($geoPointSearchDto->prefixes) {
            $qb->andWhere('gp.prefixBe IN (:prefixes)')
                ->setParameter('prefixes', $geoPointSearchDto->prefixes);
        }

        if (null !== $geoPointSearchDto->district) {
            $exReg = $qb->expr()->orX('gp.district = :district', 'LENGTH(gp.district) = 0');
            $qb->andWhere($exReg)
                ->setParameter('district', $geoPointSearchDto->district);
        }

        if (null !== $geoPointSearchDto->subDistrict) {
            $exReg = $qb->expr()->orX('gp.subdistrict = :subDistrict', 'LENGTH(gp.subdistrict) = 0');
            $qb->andWhere($exReg)
                ->setParameter('subDistrict', $geoPointSearchDto->subDistrict);
        }

        if (null !== $geoPointSearchDto->region) {
            $exReg = $qb->expr()->orX('gp.region = :region', 'LENGTH(gp.region) = 0');
            $qb->andWhere($exReg)
                ->setParameter('region', $geoPointSearchDto->region);
        }

        if (null !== $geoPointSearchDto->limit) {
            $qb->setMaxResults($geoPointSearchDto->limit);
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }
}
