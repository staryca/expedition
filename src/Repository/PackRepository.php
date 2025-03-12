<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Pack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pack>
 */
class PackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pack::class);
    }

    public function getPackByName(?string $name): ?Pack
    {
        if (empty($name)) {
            return null;
        }

        return $this->createQueryBuilder('p')
            ->where('p.name = :name')
            ->setParameter('name', mb_strtolower($name))
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
