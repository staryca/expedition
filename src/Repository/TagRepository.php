<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @return array<string, Tag>
     */
    public function getAllTags(): array
    {
        $result = [];

        foreach ($this->findAll() as $tag) {
            $result[mb_strtolower($tag->getName())] = $tag;
        }

        return $result;
    }
}
