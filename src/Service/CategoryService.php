<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Additional\Musician;
use App\Entity\Type\CategoryType;
use App\Helper\TextHelper;

readonly class CategoryService
{
    public function __construct(
        private DanceService $danceService,
    ) {
    }

    public function detectCategory(?string $content, ?string $notes = null): ?int
    {
        $content = mb_strtolower((string) $content);
        [$text, $textNotes] = TextHelper::getNotes($content);

        $category = !empty($text) ? CategoryType::detectCategory($text, $notes) : null;
        if (!$category) {
            $category = CategoryType::detectCategory($textNotes, $notes);
        }

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
