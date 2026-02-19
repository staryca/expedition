<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Ritual;

class EpisodeDto
{
    private int $category;
    private string $text;
    /** @var array<string> $tags */
    public array $tags = [];
    public ?Ritual $ritual = null;

    public function __construct(int $category, string $text)
    {
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

    public function addText(string $text): void
    {
        $this->text .= "\n" . $text;
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'text' => $this->text,
            'ritual' => $this->ritual->getName(),
            'tags' => $this->tags,
        ];
    }
}
