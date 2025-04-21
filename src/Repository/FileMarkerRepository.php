<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Expedition;
use App\Entity\FileMarker;
use App\Entity\Type\CategoryType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileMarker>
 */
class FileMarkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileMarker::class);
    }

    public function getStatistics(Expedition $expedition): array
    {
        $records = $this->createQueryBuilder('fm')
            ->select('COUNT(fm.id) AS cnt', 'fm.category')
            ->leftJoin('fm.reportBlock', 'rb')
            ->leftJoin('rb.report', 'r')
            ->leftJoin('r.expedition', 'e')
            ->where('r.expedition = :expedition')
            ->setParameter('expedition', $expedition)
            ->groupBy('fm.category')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($records as $record) {
            $category = $record['category'];
            if (!in_array($category, CategoryType::SYSTEM_TYPES, true)) {
                $result[$category] = $record['cnt'];
            }
        }

        return $result;
    }
}
