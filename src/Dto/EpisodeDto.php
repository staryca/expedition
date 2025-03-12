<?php

declare(strict_types=1);

namespace App\Dto;

class EpisodeDto
{
    private int $category;
    private string $text;

    public function __construct(int $category, string $text) {
        $this->category = $category;
        $this->text = $text;
    }

    public function getCategory(): int
    {
        return $this->category;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'text' => $this->text,
        ];
    }
}
