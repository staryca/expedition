<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Additional\Musician;
use App\Entity\Type\CategoryType;

readonly class CategoryService
{
    public function __construct(
        private DanceService $danceService,
    ) {
    }

    public function detectCategory(?string $content, ?string $notes = null): ?int
    {
        $text = mb_strtolower((string) $content);

        $category = CategoryType::detectCategory($text, $notes);
        $isDance = $category === CategoryType::QUADRILLE || $this->danceService->isDance($text);
        if ($category === null) {
            $isMusician = Musician::isMusician($notes);
            if ($isDance && ($isMusician || empty($notes))) {
                $category = CategoryType::MELODY;
            }
        }

        return $category;
    }
}
