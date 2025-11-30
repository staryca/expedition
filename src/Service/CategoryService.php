<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Additional\Musician;
use App\Entity\Type\CategoryType;
use App\Repository\DanceRepository;

class CategoryService
{
    private ?array $dances = null;

    public function __construct(
        private readonly DanceRepository $danceRepository,
    ) {
    }

    private function getDances(): array
    {
        $dances = [];

        $objects = $this->danceRepository->findAll();
        foreach ($objects as $object) {
            $dances[] = mb_strtolower($object->getName());
        }

        return $dances;
    }

    private function isDance(string $text): bool
    {
        $text = mb_strtolower($text);

        if (null === $this->dances) {
            $this->dances = $this->getDances();
        }

        foreach ($this->dances as $dance) {
            if (mb_strpos($text, $dance) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectCategory(?string $content, ?string $notes = null): ?int
    {
        $text = mb_strtolower((string) $content);

        $category = CategoryType::detectCategory($text, $notes);
        if ($category === null) {
            $isDance = $this->isDance($text);
            $isMusician = Musician::isMusician($notes);
            if ($isDance && $isMusician) {
                $category = CategoryType::MELODY;
            }
        }

        return $category;
    }
}
