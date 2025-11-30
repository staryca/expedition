<?php

declare(strict_types=1);

namespace App\Dto;

class TreeItemDto
{
    public function __construct(
        private int $id,
        private string $name,
        private ?int $parentId = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }
}
