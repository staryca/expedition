<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Expedition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Expedition>
 */
class ExpeditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expedition::class);
    }

    /**
     * @return array<Expedition>
     */
    public function findAllWithReports(): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.reports', 'r')
            ->addSelect('r')
            ->setMaxResults(200)
            ->addOrderBy('r.code', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findActive(): ?Expedition
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isActive = :val')
            ->setParameter('val', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
