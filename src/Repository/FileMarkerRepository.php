<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\LatLonDto;
use App\Entity\Additional\FileMarkerAdditional;
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

    public function getStatistics(?Expedition $expedition): array
    {
        $qb = $this->createQueryBuilder('fm')
            ->select('COUNT(fm.id) AS cnt', 'fm.category')
            ->leftJoin('fm.reportBlock', 'rb')
            ->leftJoin('rb.report', 'r')
            ->groupBy('fm.category');

        if ($expedition) {
            $qb->andWhere('r.expedition = :expedition')
                ->setParameter('expedition', $expedition);
        }

        $records = $qb
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
     * @param int|null $category
     * @return array<FileMarker>
     */
    public function getMarkersByExpedition(Expedition $expedition, ?int $category = null): array
    {
        $qb = $this->createQueryBuilder('fm')
            ->leftJoin('fm.reportBlock', 'rb')
            ->leftJoin('fm.file', 'f')
            ->leftJoin('f.reportBlock', 'rb2')
            ->leftJoin('rb.report', 'r')
            ->leftJoin('rb2.report', 'r2')
            ->where('r.expedition = :expedition')
            ->orWhere('r2.expedition = :expedition')
            ->setParameter('expedition', $expedition);

        if ($category) {
            $qb->andWhere('fm.category = :category')
                ->setParameter('category', $category);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Expedition $expedition
     * @param array<string, bool> $filterMarkerAdditional
     * @param bool $random
     * @return array<FileMarker>
     */
    public function getMarkersWithFullObjects(Expedition $expedition, array $filterMarkerAdditional = [], bool $random = false): array
    {
        $qb = $this->createQueryBuilder('fm')
            ->addSelect('f')
            ->addSelect('rb')
            ->addSelect('rb2')
            ->addSelect('r')
            ->addSelect('r2')
            ->leftJoin('fm.reportBlock', 'rb')
            ->leftJoin('fm.file', 'f')
            ->leftJoin('f.reportBlock', 'rb2')
            ->leftJoin('rb.report', 'r')
            ->leftJoin('rb2.report', 'r2')
            ->where('r.expedition = :expedition')
            ->orWhere('r2.expedition = :expedition')
            ->setParameter('expedition', $expedition);

        foreach ($filterMarkerAdditional as $field => $value) {
            $sign = $value ? '> 0' : 'IS NULL';
            $qb->andWhere("JSON_GET_FIELD_AS_INTEGER(fm.additional, '" . $field . "') " . $sign);
        }

        if ($random) {
            $qb->orderBy('RANDOM()');
        } else {
            $qb->orderBy('fm.publish', 'ASC');
        }

        return $qb
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
            ->addOrderBy('fm.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param LatLonDto|null $dto
     * @param string|null $district
     * @param int|null $category
     * @return array<FileMarker>
     */
    public function getMarkersInLocation(?LatLonDto $dto = null, ?string $district = null, ?int $category = null): array
    {
        $qb = $this->createQueryBuilder('fm');

        $qb
            ->leftJoin('fm.reportBlock', 'rb')
            ->leftJoin('fm.file', 'f')
            ->leftJoin('f.reportBlock', 'rb2')
            ->leftJoin('rb.report', 'r')
            ->leftJoin('rb2.report', 'r2')
            ->leftJoin('r.geoPoint', 'gp')
            ->leftJoin('r2.geoPoint', 'gp2')
            ->orderBy('fm.category', 'ASC')
            ->addOrderBy('fm.name', 'ASC');

        if ($dto) {
            $qb->where(
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
                ->setParameter('minLon', $dto->lon - LocationService::POINT_NEAR * LocationService::LAT_LON_RATE)
                ->setParameter('maxLon', $dto->lon + LocationService::POINT_NEAR * LocationService::LAT_LON_RATE);
        }

        if ($district) {
            $qb->andWhere($qb->expr()->orX('gp.district = :district', 'gp2.district = :district'))
                ->setParameter('district', $district);
        }

        if ($category) {
            $qb->andWhere('fm.category = :category')
                ->setParameter('category', $category);
        }

        return $qb
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
