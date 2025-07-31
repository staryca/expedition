<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\LatLonDto;
use App\Entity\Expedition;
use App\Entity\FileMarker;
use App\Entity\GeoPoint;
use App\Entity\Type\CategoryType;
use App\Service\LocationService;
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

    /**
     * @param Expedition $expedition
     * @return array<FileMarker>
     */
    public function getMarkersByExpedition(Expedition $expedition): array
    {
        return $this->createQueryBuilder('fm')
            ->leftJoin('fm.reportBlock', 'rb')
            ->leftJoin('fm.file', 'f')
            ->leftJoin('f.reportBlock', 'rb2')
            ->leftJoin('rb.report', 'r')
            ->leftJoin('rb2.report', 'r2')
            ->where('r.expedition = :expedition')
            ->orWhere('r2.expedition = :expedition')
            ->setParameter('expedition', $expedition)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param GeoPoint $geoPoint
     * @return array<FileMarker>
     */
    public function getMarkersByGeoPoint(GeoPoint $geoPoint): array
    {
        return $this->createQueryBuilder('fm')
            ->leftJoin('fm.reportBlock', 'rb')
            ->leftJoin('fm.file', 'f')
            ->leftJoin('f.reportBlock', 'rb2')
            ->leftJoin('rb.report', 'r')
            ->leftJoin('rb2.report', 'r2')
            ->where('r.geoPoint = :geoPoint')
            ->orWhere('r2.geoPoint = :geoPoint')
            ->setParameter('geoPoint', $geoPoint)
            ->orderBy('fm.category', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMarkersNearGeoPoint(LatLonDto $dto): array
    {
        $qb = $this->createQueryBuilder('fm');

        return $qb
            ->leftJoin('fm.reportBlock', 'rb')
            ->leftJoin('fm.file', 'f')
            ->leftJoin('f.reportBlock', 'rb2')
            ->leftJoin('rb.report', 'r')
            ->leftJoin('rb2.report', 'r2')
            ->leftJoin('r.geoPoint', 'gp')
            ->leftJoin('r2.geoPoint', 'gp2')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        'gp.lat between :minLat and :maxLat',
                        'gp.lon between :minLon and :maxLon',
                    ),
                    $qb->expr()->andX(
                        'gp2.lat between :minLat and :maxLat',
                        'gp2.lon between :minLon and :maxLon',
                    )
                )
            )
            ->setParameter('minLat', $dto->lat - LocationService::POINT_NEAR)
            ->setParameter('maxLat', $dto->lat + LocationService::POINT_NEAR)
            ->setParameter('minLon', $dto->lon - LocationService::POINT_NEAR)
            ->setParameter('maxLon', $dto->lon + LocationService::POINT_NEAR)
            ->orderBy('fm.category', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param FileMarker $fileMarker
     * @return array<string>
     */
    public function getTagNamesByMarker(FileMarker $fileMarker): array
    {
        $records = $this->createQueryBuilder('fm')
            ->select('t.name')
            ->leftJoin('fm.tags', 't')
            ->where('fm.id = :markerId')
            ->setParameter('markerId', $fileMarker->getId())
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($records as $record) {
            $result[] = $record['name'];
        }

        return $result;
    }
}
