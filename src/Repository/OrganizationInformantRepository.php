<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OrganizationInformant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizationInformant>
 */
class OrganizationInformantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationInformant::class);
    }
}
