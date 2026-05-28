<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Region;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Region>
 */
class RegionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Region::class);
    }

    /**
     * @return array<int, string>
     */
    public function getAllRegions(): array
    {
        $regions = [];

        foreach ($this->findAll() as $region) {
            $regions[$region->getId()] = $region->getName();
        }

        return $regions;
    }
}
