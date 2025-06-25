<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ReportBlock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReportBlock>
 */
class ReportBlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportBlock::class);
    }

    /**
     * @return array<ReportBlock>
     */
    public function findNotIndexed(): array
    {
        return $this->createQueryBuilder('rb')
            ->where('rb.searchIndex IS NULL')
            ->orWhere('rb.searchIndex = \'\'')
            ->setMaxResults(300)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<ReportBlock>
     */
    public function findByQuerySimple(string $query): array
    {
        return $this->createQueryBuilder('rb')
            ->where('rb.searchIndex LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('rb.id', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<ReportBlock>
     */
    public function findByQueryIndex(string $query): array
    {
        $records = $this->createQueryBuilder('rb')
            ->addSelect('TS_HEADLINE(:lang, rb.searchIndex, WEBSEARCH_TO_TSQUERY(:lang, :query)) AS searchHeadline')
            ->where('TSMATCH(TO_TSVECTOR(:lang, rb.searchIndex), WEBSEARCH_TO_TSQUERY(:lang, :query)) = true')
            ->setParameter('query', $query)
            ->setParameter('lang', 'belarusian')
            ->orderBy('TS_RANK(TO_TSVECTOR(:lang, rb.searchIndex), WEBSEARCH_TO_TSQUERY(:lang, :query))', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
        ;

        $result = [];
        foreach ($records as $record) {
            $reportBlock = $record[0];
            $reportBlock->setSearchHeadline($record['searchHeadline']);

            $result[] = $reportBlock;
        }

        return $result;
    }
}
